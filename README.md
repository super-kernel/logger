<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Logger

`super-kernel/logger` provides the standard output logger that SuperKernel's packages write to, and a factory for the Monolog loggers an application creates for itself.

## Installation

```bash
composer require super-kernel/logger
```

The package uses Monolog and has no requirement beyond PHP. The standard output logger is a container service: it is found in the metadata that `skernel build` generates, so a project needs SKERNEL to build it.

## What it provides

- `StdoutLoggerInterface`, which writes `INFO` and above to the standard output in a readable console format.
- Colors on an interactive terminal, and plain output otherwise.
- `LoggerFactory`, which creates a Monolog logger with one stream handler for a channel, a stream, a level, and an optional formatter.
- `ConsoleFormatter`, the console format, for loggers of your own.
- Replacement of the standard output logger by an application `#[Provider]`.

It does not:

- bind `Psr\Log\LoggerInterface`; an application chooses that service;
- replace placeholders or show context in the console format;
- read environment files, redact data, rotate files, or buffer records;
- send records to remote services or write asynchronously.

## Quick start

Write to the standard output logger from a service. Save it as `src/Billing/InvoiceImporter.php`:

```php
<?php

declare(strict_types=1);

namespace Src\Billing;

use SuperKernel\Contract\StdoutLoggerInterface;

final readonly class InvoiceImporter
{
    public function __construct(private StdoutLoggerInterface $logger)
    {
    }

    public function import(string $invoiceNumber): void
    {
        $this->logger->debug("Reading invoice {$invoiceNumber}.");
        $this->logger->info("Invoice {$invoiceNumber} imported.");
    }
}
```

Use the service from a script. Save it as `bin/import.php`:

```php
<?php

declare(strict_types=1);

use Src\Billing\InvoiceImporter;
use SuperKernel\Context\ApplicationContext;

require dirname(__DIR__) . '/vendor/autoload.php';

ApplicationContext::getContainer()->get(InvoiceImporter::class)->import('INV-1001');
```

Build the project and run the script:

```bash
skernel build
php bin/import.php
```

It prints a line such as `2026-11-10T10:56:25.283+00:00 [INFO] Invoice INV-1001 imported.`; the `DEBUG` record is below the threshold.

## Public surface

| Name | Kind | Purpose |
| --- | --- | --- |
| `SuperKernel\Contract\StdoutLoggerInterface` | Interface | Provided by the container; the PSR-3 logger that writes to the standard output. |
| `SuperKernel\Logger\Factory\LoggerFactory` | Class | `create()` returns a new Monolog logger for a channel, stream, minimum level, and optional formatter. |
| `SuperKernel\Logger\Formatter\ConsoleFormatter` | Class | Formats a record as `<timestamp> [<LEVEL>] <message>`, with colors when `decorated` is `true`. |

## Behavior to know

- The standard output logger writes to `php://stdout`, so its records mix with a command's own output. Replace it with one that writes to `php://stderr` or a file when the standard output must stay machine-readable.
- The console format shows the message as given: placeholders such as `{id}` are not replaced, and context and extra data are not shown.
- Colors are used only when the standard output is a terminal, `NO_COLOR` is not set, and `TERM` is not `dumb`.
- Replace the logger with a class marked `#[Provider(StdoutLoggerInterface::class)]`. A second `#[FactoryProvider]` for the interface makes every lookup fail with `Factory provider for SuperKernel\Contract\StdoutLoggerInterface is declared by both <class> and SuperKernel\Logger\Provider\StdoutLoggerProvider.`
- Run `skernel build` again after adding or changing a replacement, or start with `skernel run`, which builds first.
- `LoggerFactory::create()` raises `Psr\Log\InvalidArgumentException` for an unknown level; a stream that cannot be opened or written fails when the first record is written.

## Documentation

The developer documentation describes the console format, creating loggers and binding `LoggerInterface`, replacing the standard output logger, and keeping machine-readable output clean: [SuperKernel Logger](https://github.com/super-kernel/.github/blob/main/docs/components/logger.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
