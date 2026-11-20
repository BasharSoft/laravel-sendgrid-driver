# Architecture and usage

About 670 lines of PHP in `src/`. Requires `illuminate/mail >=7.0`, Guzzle 6/7, `sendgrid/sendgrid ^6.0` (`composer.json`). Laravel auto-discovers `SendgridTransportServiceProvider` and `EmailCheckerServiceProvider` (`extra.laravel.providers`).

## Classes

| Class | File | Role |
|---|---|---|
| `SendgridTransportServiceProvider` | `src/Providers/SendgridTransportServiceProvider.php` | After `MailManager` resolves, registers transport `sendgrid`: `new SendGrid(services.sendgrid.api_key)`, the bound `EmailCheckerContract`, `services.sendgrid` and `mail` config |
| `EmailCheckerServiceProvider` | `src/Providers/EmailCheckerServiceProvider.php` | Default binding of `EmailCheckerContract` to the package's `EmailChecker` (accepts every address) |
| `MailServiceProvider` | `src/Providers/MailServiceProvider.php` | Optional replacement for Laravel's mail provider that also registers the transport |
| `SendgridTransport` | `src/Transport/SendgridTransport.php` | Converts the Swift message and posts it to `mail/send` |
| `EmailCheckerContract` | `src/Contracts/EmailCheckerContract.php` | `isValidEmail()`, `getValidEmails()` |
| `MailParams` + `MailExtender` trait | `src/Helpers/` | Attach SendGrid extras (send-at time, categories, custom args) to a message by embedding a fake `Swift_Image` whose body is a `MailParams` object |

## What the transport sends

`SendgridTransport::buildMail`:

| Part | Source |
|---|---|
| From | Message `From`, else `mail.from` |
| Subject, reply-to | Message |
| Content | `text/plain` and/or `text/html` (multipart children for plain text) |
| Attachments | Swift attachments and images up to 7 MB (`MAXIMUM_FILE_SIZE`), base64 |
| Recipients | To, Cc, Bcc, de-duplicated, each passed through `EmailCheckerContract::isValidEmail` |
| Testing mode | If `mail.testing.is_enabled`, all recipients are replaced by `mail.testing.address` |
| Sandbox | `services.sendgrid.sandbox_mode` |
| Extras | `MailParams`: `send_at` (also sets the `Date` header), categories, custom args |

After sending it adds the SendGrid `X-Message-Id` header to the Swift message.

## Tests

`tests/` (PHPUnit `~5.7`): `SendgridTransportServiceProviderTest`, `Transport/SendgridTransportTest`. They need a `tests/config/sendgrid.php` that is git-ignored.
