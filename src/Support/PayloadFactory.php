<?php

declare(strict_types=1);

namespace Hekal\ShipBridge\Turbo\Support;

use Hekal\ShipBridge\DTOs\Address;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\ExchangeShipmentRequest;
use Hekal\ShipBridge\DTOs\Parcel;
use Hekal\ShipBridge\DTOs\ReturnShipmentRequest;
use Hekal\ShipBridge\Exceptions\ShipBridgeException;

/**
 * Maps ShipBridge DTOs → Turbo External API add-order payload.
 *
 * Extra fields via metadata:
 * - government / area (governorate + neighborhood — required)
 * - phone2 / followup_phone
 * - second_client / order_summary / notes
 * - cod / return_amount / can_open / is_order
 * - invoice_number
 */
final class PayloadFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(CreateShipmentRequest $request): array
    {
        return $this->order(
            destination: $request->destination,
            origin: $request->origin,
            parcels: $request->parcels,
            reference: $request->reference,
            metadata: $request->metadata,
            isOrder: (int) ($request->metadata['is_order'] ?? 0),
            returnSummary: (string) ($request->metadata['return_summary'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function returnShipment(ReturnShipmentRequest $request): array
    {
        $pickup = $request->pickupFrom ?? $request->returnTo;
        $meta = array_merge($request->metadata, [
            'notes' => $request->reason ?? ($request->metadata['notes'] ?? 'Return shipment'),
            'return_summary' => $request->reason ?? 'Return',
        ]);

        return $this->order(
            destination: $request->returnTo,
            origin: $pickup,
            parcels: $request->parcels ?? [new Parcel(weightKg: 1.0)],
            reference: $request->originalShipmentId,
            metadata: $meta,
            isOrder: 1,
            returnSummary: (string) ($meta['return_summary'] ?? 'Return'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function exchange(ExchangeShipmentRequest $request): array
    {
        $meta = array_merge($request->metadata, [
            'notes' => $request->reason ?? ($request->metadata['notes'] ?? 'Exchange shipment'),
            'return_summary' => $request->reason ?? 'Exchange',
        ]);

        return $this->order(
            destination: $request->destination,
            origin: $request->origin,
            parcels: $request->outboundParcels,
            reference: $request->originalShipmentId,
            metadata: $meta,
            isOrder: 1,
            returnSummary: (string) ($meta['return_summary'] ?? 'Exchange'),
        );
    }

    /**
     * @param  list<Parcel>  $parcels
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function order(
        Address $destination,
        Address $origin,
        array $parcels,
        ?string $reference,
        array $metadata,
        int $isOrder,
        string $returnSummary,
    ): array {
        $phone = $destination->phone ?? (isset($metadata['phone']) ? (string) $metadata['phone'] : null);
        if ($phone === null || $phone === '') {
            throw ShipBridgeException::carrierFailed('Turbo requires phone1 (Address::$phone on destination).');
        }

        $government = (string) ($metadata['government'] ?? $metadata['governorate'] ?? $destination->city);
        $area = (string) ($metadata['area'] ?? $metadata['neighborhood'] ?? $metadata['zone'] ?? $destination->state ?? '');
        if ($government === '') {
            throw ShipBridgeException::carrierFailed('Turbo requires government (city / metadata.government).');
        }
        if ($area === '') {
            throw ShipBridgeException::carrierFailed('Turbo requires area (neighborhood / metadata.area).');
        }

        $cod = (float) ($metadata['cod'] ?? $metadata['amount_to_be_collected'] ?? 0);
        $summary = (string) ($metadata['order_summary'] ?? $this->summaryFromParcels($parcels));
        $followup = (string) (
            $metadata['followup_phone']
            ?? $metadata['api_followup_phone']
            ?? $origin->phone
            ?? $phone
        );

        $invoice = $metadata['invoice_number'] ?? $reference ?? '';

        return [
            'main_client_code' => $metadata['main_client_code']
                ?? $this->config['main_client_code']
                ?? '',
            'second_client' => $metadata['second_client'] ?? $this->config['second_client'] ?? null,
            'receiver' => $destination->name,
            'phone1' => $phone,
            'phone2' => $metadata['phone2'] ?? $destination->phone ?? '',
            'api_followup_phone' => $followup !== '' ? $followup : $phone,
            'government' => $government,
            'area' => $area,
            'address' => trim($destination->line1.($destination->line2 !== null && $destination->line2 !== '' ? ', '.$destination->line2 : '')),
            'notes' => (string) ($metadata['notes'] ?? 'No notes'),
            'invoice_number' => is_scalar($invoice) ? (string) $invoice : '',
            'order_summary' => $summary !== '' ? $summary : 'Goods',
            'amount_to_be_collected' => $cod,
            'return_amount' => (float) ($metadata['return_amount'] ?? $this->config['return_amount'] ?? 0),
            'is_order' => $isOrder,
            'return_summary' => $returnSummary,
            'can_open' => (int) ($metadata['can_open'] ?? $this->config['can_open'] ?? 1),
        ];
    }

    /**
     * @param  list<Parcel>  $parcels
     */
    private function summaryFromParcels(array $parcels): string
    {
        $parts = [];
        foreach ($parcels as $parcel) {
            if ($parcel->description !== null && $parcel->description !== '') {
                $parts[] = $parcel->description;
            }
        }

        return implode(', ', $parts);
    }
}
