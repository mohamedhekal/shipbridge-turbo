<?php

declare(strict_types=1);

use Hekal\ShipBridge\DTOs\Address;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\Parcel;
use Hekal\ShipBridge\DTOs\ReturnShipmentRequest;
use Hekal\ShipBridge\Enums\ShipmentStatus;
use Hekal\ShipBridge\Exceptions\ShipBridgeException;
use Hekal\ShipBridge\Facades\ShipBridge;
use Illuminate\Support\Facades\Http;

it('creates a Turbo order via add-order', function (): void {
    Http::fake([
        'https://backoffice.turbo-eg.com/external-api/add-order' => Http::response([
            'success' => true,
            'result' => [
                'code' => 'TRB-100',
                'bar_code' => 'TB123456',
                'invoice_number' => 'ORD-1',
            ],
        ], 200),
    ]);

    $result = ShipBridge::driver('turbo')->createShipment(new CreateShipmentRequest(
        origin: new Address('Warehouse', '1 Industrial', 'Cairo', 'EG', phone: '01011111111'),
        destination: new Address('Customer', '12 Nile', 'Giza', 'EG', phone: '01000000000', state: 'Dokki'),
        parcels: [new Parcel(weightKg: 1.2, description: 'Shoes')],
        reference: 'ORD-1',
        metadata: [
            'cod' => 250,
            'government' => 'الجيزة',
            'area' => 'الدقي',
        ],
    ));

    expect($result->trackingNumber)->toBe('TB123456')
        ->and($result->id)->toBe('TRB-100')
        ->and($result->carrier)->toBe('turbo')
        ->and($result->status)->toBe(ShipmentStatus::Created)
        ->and($result->labelUrl)->toContain('TB123456');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return str_ends_with($request->url(), '/add-order')
            && ($body['authentication_key'] ?? null) === 'test-auth-key'
            && ($body['main_client_code'] ?? null) == '55159'
            && ($body['phone1'] ?? null) === '01000000000'
            && ($body['government'] ?? null) === 'الجيزة'
            && ($body['area'] ?? null) === 'الدقي'
            && ($body['amount_to_be_collected'] ?? null) == 250
            && ($body['can_open'] ?? null) == 1;
    });
});

it('tracks via get-status', function (): void {
    Http::fake([
        'https://backoffice.turbo-eg.com/external-api/get-status' => Http::response([
            'success' => true,
            'result' => [
                'bar_code' => 'TB123456',
                'status' => 'OUT FOR DELIVERY',
                'area' => 'Dokki',
            ],
        ], 200),
    ]);

    $tracking = ShipBridge::driver('turbo')->track('TB123456');

    expect($tracking->status)->toBe(ShipmentStatus::OutForDelivery)
        ->and($tracking->events)->not->toBeEmpty();
});

it('falls back to search-order when get-status fails', function (): void {
    Http::fake([
        'https://backoffice.turbo-eg.com/external-api/get-status' => Http::response([
            'success' => false,
            'message' => 'Not found',
        ], 200),
        'https://backoffice.turbo-eg.com/external-api/search-order' => Http::response([
            'success' => true,
            'result' => [
                'bar_code' => 'TB123456',
                'status' => 'DELIVERED',
            ],
        ], 200),
    ]);

    $tracking = ShipBridge::driver('turbo')->track('TB123456');

    expect($tracking->status)->toBe(ShipmentStatus::Delivered);
});

it('returns public tracking URL as label', function (): void {
    $label = ShipBridge::driver('turbo')->label('TB123456');

    expect($label->url)->toContain('codes=TB123456')
        ->and($label->contents)->toBe('');
});

it('creates a return order', function (): void {
    Http::fake([
        'https://backoffice.turbo-eg.com/external-api/add-order' => Http::response([
            'success' => true,
            'result' => ['bar_code' => 'TB-RET-1', 'code' => 'R1'],
        ], 200),
    ]);

    $result = ShipBridge::driver('turbo')->createReturn(new ReturnShipmentRequest(
        originalShipmentId: 'TB123456',
        returnTo: new Address('Warehouse', '1 Industrial', 'Cairo', 'EG', phone: '01011111111'),
        pickupFrom: new Address('Customer', '12 Nile', 'Giza', 'EG', phone: '01000000000', state: 'Dokki'),
        reason: 'Wrong size',
        metadata: [
            'government' => 'الجيزة',
            'area' => 'الدقي',
        ],
    ));

    expect($result->status)->toBe(ShipmentStatus::Returned)
        ->and($result->trackingNumber)->toBe('TB-RET-1');

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return ($body['is_order'] ?? null) == 1
            && ($body['return_summary'] ?? null) === 'Wrong size';
    });
});

it('requires phone and area', function (): void {
    ShipBridge::driver('turbo')->createShipment(new CreateShipmentRequest(
        origin: new Address('Warehouse', '1 Industrial', 'Cairo', 'EG'),
        destination: new Address('Customer', '12 Nile', 'Giza', 'EG'),
        parcels: [new Parcel(weightKg: 1.0)],
    ));
})->throws(ShipBridgeException::class);
