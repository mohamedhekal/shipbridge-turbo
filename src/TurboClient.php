<?php

declare(strict_types=1);

namespace Hekal\ShipBridge\Turbo;

use Hekal\ShipBridge\Exceptions\ShipBridgeException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Turbo Egypt External API client.
 *
 * Base: https://backoffice.turbo-eg.com/external-api
 * Auth: authentication_key in JSON body (from Turbo merchant dashboard).
 */
final class TurboClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly array $config,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addOrder(array $payload): array
    {
        return $this->post('add-order', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchOrder(string $barcode): array
    {
        return $this->post('search-order', [
            'search_Key' => $barcode,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(string $barcode): array
    {
        return $this->post('get-status', [
            'search_Key' => $barcode,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteOrder(string $barcode): array
    {
        return $this->post('delete-order', [
            'search_Key' => $barcode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function editOrder(array $payload): array
    {
        return $this->post('edit-order', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $payload = array_merge([
            'authentication_key' => $this->authenticationKey(),
        ], $payload);

        $response = $this->request()->post($path, $payload);

        return $this->decode($response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://backoffice.turbo-eg.com/external-api'), '/'))
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-ShipBridge-Carrier' => 'turbo',
            ]);
    }

    private function authenticationKey(): string
    {
        $key = $this->config['authentication_key']
            ?? $this->config['api_key']
            ?? $this->config['token']
            ?? null;

        if (! is_string($key) || $key === '') {
            throw ShipBridgeException::carrierFailed(
                'Turbo requires TURBO_AUTHENTICATION_KEY (merchant dashboard authentication_key).'
            );
        }

        return $key;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        $success = $json['success'] ?? null;
        $ok = $success === true || $success === 1 || $success === '1';

        if ($response->successful() && $ok) {
            return $json;
        }

        $message = (string) (
            $json['message']
            ?? $json['error_msg']
            ?? $json['error']
            ?? $response->body()
        );

        if (isset($json['errors']) && is_array($json['errors'])) {
            $parts = [];
            foreach ($json['errors'] as $field => $msgs) {
                $parts[] = $field.': '.(is_array($msgs) ? implode(', ', $msgs) : (string) $msgs);
            }
            if ($parts !== []) {
                $message = ($message !== '' ? $message.' — ' : '').implode('; ', $parts);
            }
        }

        throw ShipBridgeException::carrierFailed(
            $message !== '' ? $message : 'Turbo API request failed.',
            $response->status(),
        );
    }
}
