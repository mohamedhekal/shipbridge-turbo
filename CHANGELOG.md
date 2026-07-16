# Changelog

## v0.2.0 — 2026-07-16

- Full Turbo Egypt External API driver (`backoffice.turbo-eg.com`)
- Auth via `authentication_key` in JSON body
- Create (`add-order`), track (`get-status` + `search-order`), delete helper
- Returns / exchanges mapped to `add-order` with `is_order=1`
- Label returns public tracking URL (no AWB PDF on External API)
- Arabic + English docs
- Pest Http::fake coverage

## v0.1.1 — 2026-07-16

- Documentation env fixes

## v0.1.0 — 2026-07-16

- Initial scaffold
