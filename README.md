# ShipBridge · Turbo


[![CI](https://github.com/mohamedhekal/shipbridge-turbo/actions/workflows/tests.yml/badge.svg)](https://github.com/mohamedhekal/shipbridge-turbo/actions)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/mohamedhekal/shipbridge-turbo.svg)](https://packagist.org/packages/mohamedhekal/shipbridge-turbo)

**Turbo** shipping driver for [ShipBridge](https://github.com/mohamedhekal/shipbridge) · Region: **Egypt** / **مصر**

---

## بالعربي — في ٣ خطوات

### ١) ثبّت الحزمتين
```bash
composer require mohamedhekal/shipbridge mohamedhekal/shipbridge-turbo
```

### ٢) حط مفاتيح Turbo في `.env`
```env
SHIPBRIDGE_DRIVER=turbo
TURBO_API_KEY=your-key-here
TURBO_BASE_URL=https://api.turbo.com.eg/v1
```
> لو الشركة بتستخدم username/password أو OAuth، شوف ملف `config/turbo.php`.

### ٣) ابعت شحنة
```php
use Hekal\ShipBridge\Facades\ShipBridge;
use Hekal\ShipBridge\DTOs\Address;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\Parcel;

$shipment = ShipBridge::driver('turbo')->createShipment(new CreateShipmentRequest(
    origin: new Address('المخزن', 'شارع ١', 'القاهرة', 'EG'),
    destination: new Address('العميل', 'شارع النيل', 'الجيزة', 'EG', phone: '01000000000'),
    parcels: [new Parcel(weightKg: 1.2)],
    reference: 'ORD-42',
));

echo $shipment->trackingNumber;
```

تتبع / ليبل / مرتجع:
```php
ShipBridge::driver('turbo')->track($shipment->trackingNumber);
ShipBridge::driver('turbo')->label($shipment->id);
```

---

## English — Quick start

```bash
composer require mohamedhekal/shipbridge mohamedhekal/shipbridge-turbo
```

```env
SHIPBRIDGE_DRIVER=turbo
TURBO_API_KEY=your-key-here
```

```php
ShipBridge::driver('turbo')->createShipment(...);
ShipBridge::driver('turbo')->track('TRACKING');
ShipBridge::driver('turbo')->label('SHIPMENT_ID');
```

## How it fits

```
Your Laravel app
      │
      ▼
 ShipBridge  (one API for all carriers)
      │
      ▼
 shipbridge-turbo  ← this package (Turbo)
```

## Testing

```bash
composer install && composer test
```

## License

MIT © Mohamed Hekal
