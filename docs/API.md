# Turbo API reference

Contract aligned with Turbo Egypt merchant External API used in production integrations
(`https://backoffice.turbo-eg.com/external-api`).

## Base URL

```
https://backoffice.turbo-eg.com/external-api
```

Override with `TURBO_BASE_URL`.

## Auth

Every request body includes:

```json
{
  "authentication_key": "YOUR_KEY"
}
```

Optional merchant defaults:

| Config | Env | Purpose |
|---|---|---|
| `main_client_code` | `TURBO_MAIN_CLIENT_CODE` | Merchant account code |
| `second_client` | `TURBO_SECOND_CLIENT` | Sub-client / branch label |

## Endpoints

| Action | Method | Path |
|---|---|---|
| Create order | POST | `/add-order` |
| Search order | POST | `/search-order` body `{ "search_Key": "..." }` |
| Get status | POST | `/get-status` body `{ "search_Key": "..." }` |
| Delete order | POST | `/delete-order` body `{ "search_Key": "..." }` |
| Edit order | POST | `/edit-order` |

## add-order payload (key fields)

```json
{
  "authentication_key": "...",
  "main_client_code": "55159",
  "second_client": "Branch A",
  "receiver": "Ahmed Ali",
  "phone1": "01000000000",
  "phone2": "",
  "api_followup_phone": "01100000000",
  "government": "الجيزة",
  "area": "الدقي",
  "address": "12 Nile St",
  "notes": "Call before arrival",
  "invoice_number": "ORD-42",
  "order_summary": "Clothes",
  "amount_to_be_collected": 250,
  "return_amount": 0,
  "is_order": 0,
  "return_summary": "",
  "can_open": 1
}
```

## Success response

```json
{
  "success": true,
  "result": {
    "code": "TRB-100",
    "bar_code": "TB123456",
    "invoice_number": "ORD-42"
  }
}
```

`success: false` / `success: 0` → `ShipBridgeException`.

## Tracking

`TurboDriver::track()` calls `/get-status` first, then falls back to `/search-order`.
Both use `search_Key` = barcode / tracking number.

## Labels

Turbo External API does **not** expose AWB PDF download.
`label()` returns the public tracking URL:

```
https://turbo.info/en/tracking/?codes={barcode}
```

## Returns / exchanges

Mapped to `/add-order` with `is_order=1` and `return_summary` filled.
