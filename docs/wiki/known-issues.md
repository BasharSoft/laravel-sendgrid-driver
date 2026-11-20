# Known issues

We found these while writing this wiki from the code, as of `master` at `cffeec0`. For items marked ✓, we checked the exact lines.

The repo commits no secrets (`tests/config/services.php` reads `SENDGRID_API_KEY` from the environment with an empty default).

## Correctness

| # | Issue | Where | Notes |
|---|---|---|---|
| 1 | **SendGrid errors are not checked** | `SendgridTransport::send` ignores the response status | `sendgrid/sendgrid` v6 returns a response object for 4xx/5xx instead of throwing. So the driver reports a rejected email (bad key, bad payload) to Laravel as sent (inferred from the library's behaviour) |
| 2 | ✓ **Recipient counter is never reset** | `$numberOfRecipients` is an instance property incremented in `setRecipients` and returned with `?:` | The same transport instance handles later sends, so they return a growing count. Only affects the return value |
| 3 | ✓ **All recipients filtered → empty personalization** | `setRecipients` | If the email checker rejects every address, SendGrid gets a mail with no recipients (and, per #1, nobody sees the error) |
| 4 | **`MailParams` rides on a fake image attachment** | `MailExtender::withParams` | Works, but any change in Swift's child handling breaks it silently |

## Maintenance

| # | Issue | Notes |
|---|---|---|
| 1 | ✓ **Tied to SwiftMailer and Laravel ≤ 8** | `use Swift_*` and `Illuminate\Mail\Transport\Transport`. Laravel 9 moved to Symfony Mailer and removed both, so wuzzuf-platform cannot upgrade Laravel without replacing this package |
| 2 | ✓ **Uses removed helpers** | `array_get` needs `laravel/helpers` (wuzzuf-platform requires it) |
| 3 | **Old test stack** | PHPUnit `~5.7`; no CI (continuous integration) |
| 4 | **Fork drift** | Namespace and author are still the upstream's (`Sichikawa`), which hides that this is a modified fork |
