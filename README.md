<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Logger

`super-kernel/logger` provides Monolog-backed logger creation and the named standard-output logger used by SuperKernel runtime components. It does not bind the generic `Psr\Log\LoggerInterface`; applications must choose that service identity and policy themselves.

## Logger factory

`SuperKernel\Logger\Factory\LoggerFactory` creates a fresh synchronous Monolog logger with one stream handler. Callers select the channel and stream explicitly; the minimum level defaults to `Psr\Log\LogLevel::INFO`. An optional Monolog formatter can be attached to that handler without changing the default format used by other callers.

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

The factory returns `Psr\Log\LoggerInterface`, creates a new logger for every call, and leaves service retention to the consumer's container. Monolog opens the stream lazily; invalid levels and stream-open or write failures propagate unchanged.

The factory does not discover environment files, redact sensitive context, rotate files, buffer records, configure remote transports, or claim coroutine-safe or asynchronous Swoole delivery. Those policies belong to the service that uses the factory.

## Console format

`SuperKernel\Logger\Formatter\ConsoleFormatter` provides the readable format selected by the named standard-output logger. Its layout is inspired by Spring Boot's console output while retaining PSR-3 and PHP semantics:

```text
2026-08-18T03:05:46.007+00:00 [NOTICE] Swoole main server started in base mode.
2026-08-18T03:05:46.008+00:00 [INFO] Swoole listener "http" started on 127.0.0.1:9501 (http).
```

The formatter emits a sortable timestamp with millisecond precision, keeps each PSR-3 level compact inside square brackets, follows it with exactly one space, and renders only the message. It omits context and extra data, as well as process, separator, channel, and thread columns. A consuming runtime builds its human-readable terminal sentence explicitly; structured output belongs on a separately configured formatter and handler.

Plain output is the default. Passing `decorated: true` enables Spring Boot-inspired ANSI decoration: timestamps are faint; error levels are red; `WARNING` is yellow; `NOTICE` is cyan; and `INFO` and `DEBUG` are green. Messages remain undecorated. The consuming service decides whether its output supports ANSI decoration.

## Named stdout logger

The container supplies `SuperKernel\Contract\StdoutLoggerInterface` with a fresh logger on the `super-kernel` channel, one `php://stdout` stream handler at `INFO`, and `ConsoleFormatter`. Records reach Monolog unchanged.

ANSI decoration is enabled only for an interactive stdout when `NO_COLOR` is absent and `TERM` is not `dumb`. Redirected or opted-out output remains plain.

Framework exception records use this same named stdout service. Their callers select the PSR-3 error level and own throwable formatting; Logger does not provide a second exception-only identity, adapter, channel, or stderr transport.

## Replacing the stdout logger

Framework components write their records through the registered `StdoutLoggerInterface`, so a replacement controls the threshold, format, and stream of all of them. To replace the default service, add a production application Provider, for example `src/Logger/AppLogger.php`:

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

The example writes WARNING and above to STDERR; use INFO and `php://stdout` to keep the default threshold and destination. Keep the service as `Provider`; another same-target `FactoryProvider` would conflict with the default factory. Run `skernel build` again after adding the class so that the container discovers it; `skernel run` builds before it starts. Inject configuration or environment services if the application needs deployment-specific levels.

Logger supplies timestamp, level, formatting, and its chosen stream. The default is INFO on STDOUT, so log lines can precede a command's own output there. Use a replacement logging to STDERR or a file when stdout must be a machine-readable protocol. There is no command-specific stream switch. Existing application logs use this same service, so they follow the replacement policy too.
