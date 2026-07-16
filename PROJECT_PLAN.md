# ShipBridge Turbo — Plan

## Package
`mohamedhekal/shipbridge-turbo`

## Role
Carrier driver for **Turbo** (Egypt) on top of `mohamedhekal/shipbridge`.

## v0.1
- Implement `CarrierDriver`
- Auto-register via Laravel package discovery
- Config + status map
- Http::fake Pest tests

## Later
- Vendor-specific payload quirks
- Webhook signature verification
- Live sandbox integration tests (optional, gated by env)
