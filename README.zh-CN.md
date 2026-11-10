<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Logger

`super-kernel/logger` 提供基于 Monolog 的日志器创建，以及 SuperKernel 运行时组件所用的具名标准输出日志器。它不绑定通用的 `Psr\Log\LoggerInterface`；该服务的标识和策略由应用自行决定。

## 日志器工厂

`SuperKernel\Logger\Factory\LoggerFactory` 创建一个新的同步 Monolog 日志器，带一个流处理器。调用方显式选择通道和流；最低级别默认为 `Psr\Log\LogLevel::INFO`。可以给这个处理器附加一个可选的 Monolog 格式器，而不改变其他调用方使用的默认格式。

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

工厂返回 `Psr\Log\LoggerInterface`，每次调用都创建新的日志器，服务的保留交给使用方的容器。Monolog 延迟打开流；无效的级别以及流打开或写入失败都会原样传播。

工厂不会发现环境文件、脱敏上下文、轮转文件、缓冲记录、配置远程传输，也不声称支持协程安全或异步的 Swoole 投递。这些策略属于使用该工厂的服务。

## 控制台格式

`SuperKernel\Logger\Formatter\ConsoleFormatter` 提供具名标准输出日志器所选用的易读格式。其布局参考了 Spring Boot 的控制台输出，同时保留 PSR-3 和 PHP 的语义：

```text
2026-08-18T03:05:46.007+00:00 [NOTICE] Swoole main server started in base mode.
2026-08-18T03:05:46.008+00:00 [INFO] Swoole listener "http" started on 127.0.0.1:9501 (http).
```

格式器输出可排序的毫秒精度时间戳，把每个 PSR-3 级别紧凑地放在方括号内，后跟恰好一个空格，然后只渲染消息。它省略 context 和 extra 数据，以及进程、分隔符、通道和线程列。使用方运行时会显式构造易读的终端语句；结构化输出应使用单独配置的格式器和处理器。

默认输出不带修饰。传入 `decorated: true` 会启用参考 Spring Boot 的 ANSI 修饰：时间戳变暗；错误级别为红色；`WARNING` 为黄色；`NOTICE` 为青色；`INFO` 和 `DEBUG` 为绿色。消息本身不加修饰。输出是否支持 ANSI 修饰由使用方服务决定。

## 具名 stdout 日志器

容器提供的 `SuperKernel\Contract\StdoutLoggerInterface` 是一个新的日志器，使用 `super-kernel` 通道、一个级别为 `INFO` 的 `php://stdout` 流处理器以及 `ConsoleFormatter`。记录会原样交给 Monolog。

只有当 stdout 是交互式终端、`NO_COLOR` 未设置且 `TERM` 不是 `dumb` 时，才会启用 ANSI 修饰。重定向或主动关闭颜色的输出保持不带修饰。

Framework 的异常记录也使用这个具名 stdout 服务。由调用方选择 PSR-3 错误级别并负责异常的格式化；Logger 不提供第二个专用于异常的标识、适配器、通道或 stderr 传输。

## 替换 stdout 日志器

Framework 组件通过已注册的 `StdoutLoggerInterface` 写出记录，因此替换它即可统一控制这些记录的阈值、格式和流。要替换默认服务，请添加一个应用的生产 Provider，例如 `src/Logger/AppLogger.php`：

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

示例把 WARNING 及以上级别写到 STDERR；如果要保留默认的阈值和输出目标，改用 INFO 和 `php://stdout`。请保持该服务使用 `Provider`；再声明一个同目标的 `FactoryProvider` 会与默认工厂冲突。添加这个类之后请再次运行 `skernel build`，让容器发现它；`skernel run` 会在启动前先构建。如果应用需要按部署环境区分级别，请注入配置或环境变量服务。

Logger 负责时间戳、级别、格式和所选的流。默认是 STDOUT 上的 INFO，因此日志行可能出现在命令自身的输出之前。当 stdout 必须是机器可读的协议时，请使用写到 STDERR 或文件的替换日志器。没有针对命令的流切换开关。应用已有的日志也使用同一个服务，因此同样遵循替换后的策略。
