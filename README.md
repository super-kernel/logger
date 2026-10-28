# SuperKernel Logger

`super-kernel/logger` provides Monolog-backed logger creation and the named standard-output logger used by SuperKernel
runtime components. It does not bind the generic `Psr\Log\LoggerInterface`; applications must choose that service
identity and policy themselves.

## Source organization

Paths below are relative to `src/` and the package namespace.

| Directory | Responsibility |
| --- | --- |
| `Factory` | `LoggerFactory`: synchronous Monolog construction. |
| `Formatter` | `ConsoleFormatter`: terminal record rendering. |
| `Provider` | `StdoutLoggerProvider`: named stdout-service policy and construction. |

### Public namespaces

Use the following component declarations:

- `Formatter\ConsoleFormatter`
- `Factory\LoggerFactory`

## Logger factory

`SuperKernel\Logger\Factory\LoggerFactory` creates a fresh synchronous Monolog logger with one stream handler. Callers select
the channel and stream explicitly; the minimum level defaults to `Psr\Log\LogLevel::INFO`. An optional Monolog
formatter can be attached to that handler without changing the default format used by other callers.

```php
use Psr\Log\LogLevel;
use SuperKernel\Logger\Factory\LoggerFactory;

$logger = new LoggerFactory()->create(
    channel: 'audit',
    stream: '/var/log/application/audit.log',
    minimumLevel: LogLevel::WARNING,
);

$logger->warning('Worker {workerId} stopped.', ['workerId' => 7]);
```

The factory returns `Psr\Log\LoggerInterface`, creates a new logger for every call, and leaves service retention to the
consumer's container. Monolog opens the stream lazily; invalid levels and stream-open or write failures propagate
unchanged.

## Console format

`SuperKernel\Logger\Formatter\ConsoleFormatter` provides the readable format selected by the named standard-output logger. Its
layout is inspired by Spring Boot's console output while retaining PSR-3 and PHP semantics:

```text
2026-08-18T03:05:46.007+00:00 [NOTICE] Swoole main server started in base mode.
2026-08-18T03:05:46.008+00:00 [INFO] Swoole listener "http" started on 127.0.0.1:9501 (http).
```

The formatter emits a sortable timestamp with millisecond precision, keeps each PSR-3 level compact inside square
brackets, follows it with exactly one space, and renders only the message. It deliberately omits trailing context/extra
JSON plus repeated process, separator, channel, and Java thread columns. A consuming runtime builds its human-readable
terminal sentence explicitly; structured output belongs on a separately configured formatter and handler.

Plain output is the default. Passing `decorated: true` enables Spring Boot-inspired ANSI decoration: timestamps are
faint; error levels are red; `WARNING` is yellow; `NOTICE` is cyan; and `INFO` and `DEBUG` are green. Messages remain
undecorated.

The format does not invent a Java thread name. PHP/Swoole has no stable equivalent to Spring Boot's Java thread
column. The consuming service decides whether its output supports ANSI decoration.

## Named stdout logger

`SuperKernel\Logger\StdoutLogger` implements `SuperKernel\Contract\StdoutLoggerInterface` by delegating PSR-3 records
unchanged. `Provider\StdoutLoggerProvider` supplies that named interface with a fresh `super-kernel`
channel, one `php://stdout` stream handler at `INFO`, and `ConsoleFormatter`.

ANSI decoration is enabled only for an interactive stdout when `NO_COLOR` is absent and `TERM` is not `dumb`.
Redirected or opted-out output remains plain.

Framework exception records use this same named stdout service. Their callers select the PSR-3 error level and own
throwable formatting; Logger does not provide a second exception-only identity, adapter, channel, or stderr transport.

## Scan diagnostics

DI reports skipped classes through the actual registered `StdoutLoggerInterface`: one WARNING summary and an INFO
detail per class. The unchanged default INFO threshold shows both. A WARNING threshold shows only the summary; a
replacement logger owns all filtering and transport choices. Normal scans construct the service in the scanning child;
restore-only Phar startup constructs it in the current process and reports build diagnostics each time.

To replace the default service, add a production application Provider, for example `src/Logger/AppLogger.php`:

```php
<?php

declare(strict_types=1);

namespace Src\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Logger\Factory\LoggerFactory;
use SuperKernel\Logger\Formatter\ConsoleFormatter;

#[Provider(StdoutLoggerInterface::class)]
final readonly class AppLogger implements StdoutLoggerInterface
{
    use LoggerTrait;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $factory)
    {
        $this->logger = $factory->create(
            channel: 'application',
            stream: 'php://stderr',
            minimumLevel: LogLevel::WARNING,
            formatter: new ConsoleFormatter(),
        );
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
```

Use INFO in this example for summaries and details, and `php://stdout` if stdout is the intended destination. Keep the
service as `Provider`; another same-target `FactoryProvider` would conflict with the default factory. Apply the normal
Composer autoload refresh when adding the production class; no root test mapping is required. Inject configuration or
environment services if the application needs deployment-specific levels. Do not call
`ApplicationContext::getContainer()` from the logger constructor: scan-child root creation has not returned yet.

Logger supplies timestamp, level, formatting, and its chosen stream. The default is INFO on STDOUT. For PCNTL and
Process, default scan records reach parent STDOUT. Exec merges child streams into parent STDERR. Null/Phar uses the
current process's STDOUT. See [DI stream and failure details](https://github.com/super-kernel/di/blob/main/README.md#failures-and-process-streams).

Commands such as `list --format=json` can consequently have log lines before their JSON on stdout, even with exit 0.
Use a replacement logging to STDERR or a file when stdout must be a machine-readable protocol; changing only the
threshold to WARNING still leaves the summary. No command-specific stream switch or Logger default-stream change is
introduced. Existing application logs use this same service, so they follow the replacement policy too.

## Dependency boundary

- `psr/log:^3.0.2` is the public return and level boundary.
- `monolog/monolog:^3.12.1` supplies the selected synchronous implementation, stream handler, and formatter boundary.
- SuperKernel Contract supplies the named stdout interface, and Attribute supplies its `FactoryProvider` metadata.

The factory does not discover environment files, redact sensitive context, rotate files, buffer records, configure
remote transports, or claim coroutine-safe or asynchronous Swoole delivery. Those policies belong to the service that
uses the factory.

## Development

Use a PHP 8.4 Composer in this package's checkout:

```bash
composer install
composer validate --strict
composer test
composer format:check
composer format
```
