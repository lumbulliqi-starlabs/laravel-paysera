# Changelog

All notable changes to `laravel-paysera` will be documented in this file.

## Unreleased

### Fixed

- `PayseraService::initiate()` and `redirectToPayment()` always failed with "'accepturl' is required
  but missing": `buildRequestData()` removed the accept, cancel and callback URLs before signing the
  request. The URLs now stay in the signed data; callers may pass them or rely on config.
- Callback statuses now follow Paysera's documentation. Status `3` is "additional information about
  the payer" and `4` is "payment executed, no confirmation of received funds will follow"; the enum
  mapped them to `CANCELLED` and `REFUSED`, which would cancel a real payment. `3` is now
  `PAYER_INFO` (pending) and `4` is `EXECUTED_UNCONFIRMED` (successful). Paysera never reports a
  cancellation through the callback; `CANCELLED` and `REFUSED` remain as application-set states
  with the values `100` and `101`.
- An empty `PAYSERA_PROJECT_ID` or `PAYSERA_SIGN_PASSWORD` in `.env` no longer causes a `TypeError`
  when the service is constructed; the config coerces them to `null`.

### Added

- `PaymentResult::$payAmount` and `$payCurrency` (Paysera's `payamount` / `paycurrency`: what was
  actually paid after currency conversion) and `PaymentResult::isPending()`.
- `initiate()` sends `paytext` and `p_email` when a description or email is given.
