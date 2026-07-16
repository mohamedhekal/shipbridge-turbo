<?php

declare(strict_types=1);

namespace Hekal\ShipBridge\Turbo;

use Hekal\ShipBridge\Contracts\CarrierDriver;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\ExchangeShipmentRequest;
use Hekal\ShipBridge\DTOs\LabelResult;
use Hekal\ShipBridge\DTOs\ReturnShipmentRequest;
use Hekal\ShipBridge\DTOs\ShipmentResult;
use Hekal\ShipBridge\DTOs\TrackingEvent;
use Hekal\ShipBridge\DTOs\TrackingResult;
use Hekal\ShipBridge\Enums\LabelFormat;
use Hekal\ShipBridge\Enums\ShipmentStatus;
use Hekal\ShipBridge\Exceptions\ShipBridgeException;
use Hekal\ShipBridge\Support\StatusNormalizer;
use Hekal\ShipBridge\Turbo\Support\PayloadFactory;

/**
 * Turbo Egypt driver (External API on backoffice.turbo-eg.com).
 */
final class TurboDriver implements CarrierDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly TurboClient $client,
        private readonly PayloadFactory $payloads,
        private readonly StatusNormalizer $normalizer,
        private readonly array $config = [],
    ) {}

    public function createShipment(CreateShipmentRequest $request): ShipmentResult
    {
        $response = $this->client->addOrder($this->payloads->create($request));

        return $this->shipmentFromAddOrder($response);
    }

    public function track(string $trackingNumber): TrackingResult
    {
        $payload = $this->resolveTrackingPayload($trackingNumber);

        $statusRaw = $this->extractStatus($payload);
        $status = $this->normalizer->normalize($statusRaw);

        /** @var list<TrackingEvent> $events */
        $events = [];
        foreach ($this->extractEvents($payload) as $event) {
            $events[] = new TrackingEvent(
                status: $this->normalizer->normalize((string) ($event['status'] ?? $statusRaw)),
                description: (string) ($event['description'] ?? $event['status'] ?? $statusRaw),
                occurredAt: isset($event['occurred_at']) ? (string) $event['occurred_at'] : (isset($event['date']) ? (string) $event['date'] : null),
                location: isset($event['location']) ? (string) $event['location'] : (isset($event['area']) ? (string) $event['area'] : null),
            );
        }

        if ($events === [] && $statusRaw !== '') {
            $events[] = new TrackingEvent(
                status: $status,
                description: $statusRaw,
                occurredAt: isset($payload['updated_at']) ? (string) $payload['updated_at'] : null,
                location: isset($payload['area']) ? (string) $payload['area'] : null,
            );
        }

        return new TrackingResult(
            trackingNumber: (string) ($payload['bar_code'] ?? $payload['barcode'] ?? $trackingNumber),
            status: $status,
            events: $events,
            raw: $payload,
        );
    }

    public function label(string $shipmentId, LabelFormat $format = LabelFormat::Pdf): LabelResult
    {
        // Turbo External API does not expose AWB PDF download.
        // Provide the public tracking page URL so merchants can open / share the barcode.
        $template = (string) ($this->config['tracking_url_template'] ?? 'https://turbo.info/en/tracking/?codes={barcode}');
        $url = str_replace('{barcode}', rawurlencode($shipmentId), $template);

        return new LabelResult(
            shipmentId: $shipmentId,
            format: $format,
            contents: '',
            base64Encoded: false,
            url: $url,
        );
    }

    public function createReturn(ReturnShipmentRequest $request): ShipmentResult
    {
        $response = $this->client->addOrder($this->payloads->returnShipment($request));
        $result = $this->shipmentFromAddOrder($response);

        return new ShipmentResult(
            id: $result->id,
            trackingNumber: $result->trackingNumber,
            status: ShipmentStatus::Returned,
            carrier: 'turbo',
            labelUrl: $result->labelUrl,
            raw: $result->raw,
        );
    }

    public function createExchange(ExchangeShipmentRequest $request): ShipmentResult
    {
        $response = $this->client->addOrder($this->payloads->exchange($request));
        $result = $this->shipmentFromAddOrder($response);

        return new ShipmentResult(
            id: $result->id,
            trackingNumber: $result->trackingNumber,
            status: ShipmentStatus::Exchanged,
            carrier: 'turbo',
            labelUrl: $result->labelUrl,
            raw: $result->raw,
        );
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function shipmentFromAddOrder(array $response): ShipmentResult
    {
        /** @var array<string, mixed> $result */
        $result = is_array($response['result'] ?? null) ? $response['result'] : $response;

        $barcode = (string) ($result['bar_code'] ?? $result['barcode'] ?? $result['BarCode'] ?? '');
        $code = (string) ($result['code'] ?? $result['Code'] ?? $barcode);

        if ($barcode === '' && $code === '') {
            throw ShipBridgeException::carrierFailed('Turbo add-order returned no bar_code.');
        }

        $template = (string) ($this->config['tracking_url_template'] ?? 'https://turbo.info/en/tracking/?codes={barcode}');
        $labelUrl = str_replace('{barcode}', rawurlencode($barcode !== '' ? $barcode : $code), $template);

        return new ShipmentResult(
            id: $code !== '' ? $code : $barcode,
            trackingNumber: $barcode !== '' ? $barcode : $code,
            status: ShipmentStatus::Created,
            carrier: 'turbo',
            labelUrl: $labelUrl,
            raw: $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTrackingPayload(string $trackingNumber): array
    {
        $errors = [];

        foreach (['getStatus', 'searchOrder'] as $method) {
            try {
                $response = $method === 'getStatus'
                    ? $this->client->getStatus($trackingNumber)
                    : $this->client->searchOrder($trackingNumber);

                /** @var array<string, mixed> $inner */
                $inner = is_array($response['result'] ?? null)
                    ? $response['result']
                    : (is_array($response['data'] ?? null) ? $response['data'] : $response);

                if ($inner !== []) {
                    return $inner;
                }
            } catch (ShipBridgeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        throw ShipBridgeException::carrierFailed(
            'Turbo tracking failed for '.$trackingNumber.($errors !== [] ? ': '.implode(' | ', $errors) : '.')
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractStatus(array $payload): string
    {
        foreach (['status', 'Status', 'order_status', 'current_status', 'state', 'EnName', 'ArName'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key]) && (string) $payload[$key] !== '') {
                return (string) $payload[$key];
            }
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $nested = $payload[$key];
                if (isset($nested['value'])) {
                    return (string) $nested['value'];
                }
                if (isset($nested['name'])) {
                    return (string) $nested['name'];
                }
            }
        }

        return 'exception';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractEvents(array $payload): array
    {
        foreach (['events', 'history', 'statuses', 'timeline', 'logs'] as $key) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            $events = [];
            foreach ($payload[$key] as $row) {
                if (is_array($row)) {
                    $events[] = $row;
                }
            }

            return $events;
        }

        return [];
    }
}
