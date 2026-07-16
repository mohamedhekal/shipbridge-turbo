# ShipBridge · Turbo


[![CI](https://github.com/mohamedhekal/shipbridge-turbo/actions/workflows/tests.yml/badge.svg)](https://github.com/mohamedhekal/shipbridge-turbo/actions)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/mohamedhekal/shipbridge-turbo.svg)](https://packagist.org/packages/mohamedhekal/shipbridge-turbo)

**Turbo Egypt** shipping driver for [ShipBridge](https://github.com/mohamedhekal/shipbridge) · Region: **Egypt** / **مصر**

Real External API: `https://backoffice.turbo-eg.com/external-api`

---

## بالعربي — في ٣ خطوات

### ١) ثبّت الحزمتين
```bash
composer require mohamedhekal/shipbridge mohamedhekal/shipbridge-turbo
```

### ٢) حط مفاتيح Turbo في `.env`
```env
SHIPBRIDGE_DRIVER=turbo
TURBO_AUTHENTICATION_KEY=your-authentication-key
TURBO_MAIN_CLIENT_CODE=your-client-code
TURBO_BASE_URL=https://backoffice.turbo-eg.com/external-api
```
> التفاصيل في `config/turbo.php` و [`docs/GUIDE_AR.md`](docs/GUIDE_AR.md).

### ٣) ابعت شحنة
```php
use Hekal\ShipBridge\Facades\ShipBridge;
use Hekal\ShipBridge\DTOs\Address;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\Parcel;

$shipment = ShipBridge::driver('turbo')->createShipment(new CreateShipmentRequest(
    origin: new Address('المخزن', 'شارع ١', 'القاهرة', 'EG', phone: '01011111111'),
    destination: new Address('العميل', 'شارع النيل', 'الجيزة', 'EG', phone: '01000000000'),
    parcels: [new Parcel(weightKg: 1.2, description: 'ملابس')],
    reference: 'ORD-42',
    metadata: [
        'cod' => 250,
        'government' => 'الجيزة',
        'area' => 'الدقي',
    ],
));

echo $shipment->trackingNumber; // bar_code
```

تتبع / ليبل / مرتجع:
```php
ShipBridge::driver('turbo')->track($shipment->trackingNumber);
ShipBridge::driver('turbo')->label($shipment->trackingNumber); // رابط التتبع العام
```

---

## English — Quick start

```bash
composer require mohamedhekal/shipbridge mohamedhekal/shipbridge-turbo
```

```env
SHIPBRIDGE_DRIVER=turbo
TURBO_AUTHENTICATION_KEY=your-authentication-key
TURBO_MAIN_CLIENT_CODE=your-client-code
TURBO_BASE_URL=https://backoffice.turbo-eg.com/external-api
```

```php
ShipBridge::driver('turbo')->createShipment(...); // POST /add-order
ShipBridge::driver('turbo')->track('BARCODE');    // /get-status → /search-order
ShipBridge::driver('turbo')->label('BARCODE');    // public tracking URL
```

See [`docs/API.md`](docs/API.md) for the full External API contract.

## How it fits

```
Your Laravel app
      │
      ▼
 ShipBridge  (one API for all carriers)
      │
      ▼
 shipbridge-turbo  ← this package (Turbo Egypt)
```

## Testing

```bash
composer install && composer test
```

## License

MIT © Mohamed Hekal
