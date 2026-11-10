<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Logger

`super-kernel/logger` 提供 SuperKernel 各个包写入的标准输出日志记录器，以及用于创建应用自有 Monolog 日志记录器的工厂。

## 安装

```bash
composer require super-kernel/logger
```

本包使用 Monolog，除 PHP 外没有其他要求。标准输出日志记录器是一个容器服务：它从 `skernel build` 生成的元数据中发现，因此项目需要用 SKERNEL 构建。

## 提供的能力

- `StdoutLoggerInterface`，以易读的控制台格式把 `INFO` 及以上级别写到标准输出。
- 在交互式终端上使用颜色，其他情况下输出纯文本。
- `LoggerFactory`，按通道、流、级别和可选的格式化器创建带一个流处理器的 Monolog 日志记录器。
- `ConsoleFormatter`，即控制台格式，可用于你自己的日志记录器。
- 通过应用的 `#[Provider]` 替换标准输出日志记录器。

它不会：

- 绑定 `Psr\Log\LoggerInterface`，该服务由应用选择；
- 在控制台格式中替换占位符或显示上下文；
- 读取环境文件、脱敏数据、轮转文件或缓冲记录；
- 把记录发送到远程服务或异步写入。

## 快速开始

从一个服务写入标准输出日志记录器。将它保存为 `src/Billing/InvoiceImporter.php`：

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

从脚本中使用该服务。将它保存为 `bin/import.php`：

```php
<?php

declare(strict_types=1);

use Src\Billing\InvoiceImporter;
use SuperKernel\Context\ApplicationContext;

require dirname(__DIR__) . '/vendor/autoload.php';

ApplicationContext::getContainer()->get(InvoiceImporter::class)->import('INV-1001');
```

构建项目并运行脚本：

```bash
skernel build
php bin/import.php
```

它会打印类似 `2026-11-10T10:56:25.283+00:00 [INFO] Invoice INV-1001 imported.` 的一行；`DEBUG` 记录低于阈值。

## 公开接口

| 名称 | 类别 | 用途 |
| --- | --- | --- |
| `SuperKernel\Contract\StdoutLoggerInterface` | 接口 | 由容器提供；写到标准输出的 PSR-3 日志记录器。 |
| `SuperKernel\Logger\Factory\LoggerFactory` | 类 | `create()` 按通道、流、最低级别和可选的格式化器返回一个新的 Monolog 日志记录器。 |
| `SuperKernel\Logger\Formatter\ConsoleFormatter` | 类 | 把记录格式化为 `<timestamp> [<LEVEL>] <message>`，`decorated` 为 `true` 时使用颜色。 |

## 需要了解的行为

- 标准输出日志记录器写到 `php://stdout`，因此它的记录会与命令自己的输出混在一起。当标准输出必须保持机器可读时，请把它替换为写到 `php://stderr` 或文件的日志记录器。
- 控制台格式按原样显示消息：`{id}` 之类的占位符不会被替换，上下文和附加数据也不会显示。
- 只有当标准输出是终端、`NO_COLOR` 未设置并且 `TERM` 不是 `dumb` 时才使用颜色。
- 用带有 `#[Provider(StdoutLoggerInterface::class)]` 的类替换日志记录器。为该接口再声明一个 `#[FactoryProvider]` 会使每次查找都失败，并报告 `Factory provider for SuperKernel\Contract\StdoutLoggerInterface is declared by both <class> and SuperKernel\Logger\Provider\StdoutLoggerProvider.`
- 添加或修改替换实现之后，请重新运行 `skernel build`，或者用会先构建的 `skernel run` 启动。
- `LoggerFactory::create()` 对未知级别抛出 `Psr\Log\InvalidArgumentException`；无法打开或写入的流在写入第一条记录时失败。

## 文档

开发者文档介绍了控制台格式、创建日志记录器和绑定 `LoggerInterface`、替换标准输出日志记录器，以及如何保持机器可读的输出干净：[SuperKernel Logger](https://github.com/super-kernel/.github/blob/main/docs/components/logger.zh-CN.md)。

## 许可证

基于 MIT 许可证发布，详见 [LICENSE](LICENSE)。
