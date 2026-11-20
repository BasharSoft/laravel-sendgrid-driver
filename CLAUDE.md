# CLAUDE.md

`basharsoft/laravel-sendgrid-driver` (namespace `Sichikawa\LaravelSendgridDriver`): BasharSoft fork of `s-ichikawa/laravel-sendgrid-driver`. Adds a `sendgrid` mail transport to Laravel that turns a SwiftMailer message into a SendGrid v3 Web API call, plus two Wuzzuf hooks: an email checker that drops invalid recipients and a testing mode that redirects all mail to one address.
`illuminate/mail >=7.0` (in practice Laravel <= 8, SwiftMailer), `sendgrid/sendgrid ^6.0`, Guzzle 6/7.
Live branch: `master`. wuzzuf-platform requires `^5.0` and locks `5.2`.

## Commands

```bash
composer install
SENDGRID_API_KEY=... vendor/bin/phpunit                       # all tests (phpunit.xml, suite "all")
vendor/bin/phpunit tests/Transport/SendgridTransportTest.php  # one file
```

PHPUnit `~5.7`. Tests read `tests/config/services.php` (`SENDGRID_API_KEY`, `SENDGRID_SANDBOX_MODE` from env). No linter, no CI.

## Architecture

| Path | What |
|---|---|
| `src/Transport/SendgridTransport.php` | Hub. `send()` -> `buildMail()` (from, subject, reply-to, text/html content, attachments <= 7 MB, recipients, sandbox, `MailParams` extras) -> `POST mail/send`; sets `X-Message-Id` on the Swift message |
| `src/Providers/SendgridTransportServiceProvider.php` | Auto-discovered. After `MailManager` resolves, registers transport `sendgrid` with `services.sendgrid.api_key`, the bound `EmailCheckerContract` and `mail` config |
| `src/Providers/EmailCheckerServiceProvider.php` | Auto-discovered. Default binding of `EmailCheckerContract` to `src/Libraries/EmailChecker.php` (accepts every address) |
| `src/Providers/MailServiceProvider.php` | Optional replacement for Laravel's mail provider that also registers the transport |
| `src/Contracts/EmailCheckerContract.php` | `isValidEmail()`, `getValidEmails()` |
| `src/Helpers/MailParams.php`, `MailExtender.php` | Attach `send_at`, categories, custom args by embedding a fake `Swift_Image` carrying a `MailParams` object |
| `tests/` | `SendgridTransportServiceProviderTest`, `Transport/SendgridTransportTest`, `TestCase.php` (with `MockConfig`) |

Flow: Laravel `Mail::send` with mailer `sendgrid` -> `SendgridTransport::send(Swift_Mime_SimpleMessage)` -> `setRecipients()` (de-dup To/Cc/Bcc, filter through `EmailCheckerContract`; if `mail.testing.is_enabled`, replace all with `mail.testing.address`) -> SendGrid SDK.

## Conventions

- Config keys the transport reads: `services.sendgrid.api_key`, `services.sendgrid.sandbox_mode`, `mail.from`, `mail.testing.is_enabled`, `mail.testing.address`. Keep these names; wuzzuf-platform's config matches them.
- Consumers override `EmailCheckerContract` in their own provider (wuzzuf-platform binds `App\Libraries\EmailChecker`); keep the default permissive.
- New providers must be listed in `composer.json` `extra.laravel.providers` to be auto-discovered.
- Tests extend `tests/TestCase.php` (loaded via `autoload-dev.files`).

## Gotchas

- Built on SwiftMailer and `Illuminate\Mail\Transport\Transport`, which Laravel 9 removed. wuzzuf-platform can't upgrade Laravel without replacing this package.
- `send()` doesn't check the SendGrid response status; rejected mail looks sent.
- If the email checker rejects every recipient, SendGrid gets a mail with no recipients (and the error is hidden).
- `$numberOfRecipients` is never reset between sends on a reused transport.
- Uses `array_get`, which needs `laravel/helpers` in the host app.
- `tests/config/sendgrid.php` is git-ignored; never commit an API key there or in `tests/config/services.php`.
- Namespace and `authors` still name the upstream (`Sichikawa`); this is a modified fork.

## Connections

- Only consumer: [wuzzuf-platform](https://github.com/BasharSoft/wuzzuf-platform/blob/master/docs/wiki/README.md) (Composer, private VCS). Config in its `config/mail.php` (`mailers.sendgrid`, `testing.*`) and `config/services.php` (`sendgrid.*`); `app/Providers/MailServiceProvider.php` binds its `EmailChecker`, also used by `FilterOutgoingEmailsListener`. Whether prod sends via this mailer depends on `MAIL_MAILER` in Vault.

## Deploy

Not deployed. Tag `master` in `git@github.com:Basharsoft/laravel-sendgrid-driver.git`, then `composer update basharsoft/laravel-sendgrid-driver` in wuzzuf-platform.

## More

- Wiki: [docs/wiki/README.md](docs/wiki/README.md); [architecture.md](docs/wiki/architecture.md), [known-issues.md](docs/wiki/known-issues.md)
- Code map: [docs/wiki/code-map/index.md](docs/wiki/code-map/index.md)
- Graph: `graphify explain "SendgridTransport" --graph ../graphify/repos/laravel-sendgrid-driver/graph.json`
