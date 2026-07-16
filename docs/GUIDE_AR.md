# دليل Turbo — شرح بسيط ومفصّل

## إيه هي الحزمة دي؟

`mohamedhekal/shipbridge-turbo` تربط Laravel بشركة **تربو (Turbo Egypt)** عن طريق **ShipBridge**.

```
تطبيقك → ShipBridge → shipbridge-turbo → Turbo External API
```

---

## قبل ما تبدأ (من لوحة Turbo)

1. اعمل حساب تاجر على [turbo.info](https://turbo.info) / [business.turbo.info](https://business.turbo.info)
2. من فريق Turbo أو الداشبورد خد:
   - **authentication_key**
   - **main_client_code** (كود العميل الرئيسي)
3. تأكد إن المحافظة والمنطقة مكتوبين بنفس أسماء Turbo

---

## التثبيت

```bash
composer require mohamedhekal/shipbridge mohamedhekal/shipbridge-turbo
```

`.env`:

```env
SHIPBRIDGE_DRIVER=turbo
TURBO_AUTHENTICATION_KEY=xxxxxxxx
TURBO_MAIN_CLIENT_CODE=55159
TURBO_BASE_URL=https://backoffice.turbo-eg.com/external-api
```

---

## ابعت شحنة (COD)

```php
use Hekal\ShipBridge\Facades\ShipBridge;
use Hekal\ShipBridge\DTOs\Address;
use Hekal\ShipBridge\DTOs\CreateShipmentRequest;
use Hekal\ShipBridge\DTOs\Parcel;

$shipment = ShipBridge::driver('turbo')->createShipment(new CreateShipmentRequest(
    origin: new Address('المخزن', 'شارع الصناعة', 'القاهرة', 'EG', phone: '01011111111'),
    destination: new Address('محمد أحمد', '١٢ شارع الدقي', 'الجيزة', 'EG', phone: '01000000000'),
    parcels: [new Parcel(weightKg: 1.2, description: 'ملابس')],
    reference: 'ORD-1001',
    metadata: [
        'cod' => 450,
        'government' => 'الجيزة', // إجباري
        'area' => 'الدقي',       // إجباري
        'notes' => 'اتصل قبل الوصول',
        'can_open' => 1,
    ],
));

$shipment->trackingNumber; // bar_code من Turbo
$shipment->id;             // code من Turbo
$shipment->labelUrl;       // رابط التتبع العام
```

---

## تتبع / ليبل / مرتجع

```php
ShipBridge::driver('turbo')->track($shipment->trackingNumber);
ShipBridge::driver('turbo')->label($shipment->trackingNumber);
// الليبل = رابط صفحة التتبع العامة (Turbo مش بتعرض PDF عبر الـ External API)

ShipBridge::driver('turbo')->createReturn(...);
```

---

## ملاحظات مهمة

| البند | التفاصيل |
|---|---|
| Auth | `authentication_key` جوه JSON body |
| Create | `POST /add-order` |
| Track | `POST /get-status` ثم fallback `POST /search-order` |
| Delete | متاح عبر `TurboClient::deleteOrder()` |
| Label PDF | غير متاح عبر External API — بنرجّع رابط التتبع |

---

## Troubleshooting

- **First Phone field is required** → حط `phone` على عنوان المستلم
- **government field is required** → `metadata.government` أو `city`
- **area required** → `metadata.area` / `neighborhood` / `state`
- **Invalid authentication key** → راجع `TURBO_AUTHENTICATION_KEY`
