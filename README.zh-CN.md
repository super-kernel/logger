<div align="center">

# super-kernel/logger

**SuperKernel 框架的日志组件**

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-blue)](https://www.php.net/)
[![Monolog](https://img.shields.io/badge/monolog-^3.9-orange)](https://github.com/Seldaek/monolog)
[![SuperKernel](https://img.shields.io/badge/super--kernel-core-lightgrey)](https://github.com/super-kernel)

</div>

---

## 概述

`super-kernel/logger` 是 **SuperKernel 官方日志组件**，提供两类日志记录能力：

- **标准输出日志**：默认通过 `Logger` 类输出到终端（`stdout`），适用于开发与 CLI 环境。
- **Monolog 扩展日志**：通过 `LoggerFactory` 自动创建开发者定义的多通道日志实例，支持文件、网络、队列等多种 Handler。

本组件遵循 [psr/log](https://github.com/php-fig/log) 标准，并与 SuperKernel 的依赖注入系统与配置体系无缝集成。

---

## 安装

```bash
composer require super-kernel/logger
```

## 配置

> 此组件仅对 `#[Configuration(LoggerConfigInterface::class)]` 注解类中的 `public` 可见性的属性进行扫描。

### 单 `handler` 配置

```php
<?php
declare(strict_types=1);

namespace Src\Config\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SuperKernel\Attribute\Configuration;
use SuperKernel\Logger\Contract\LoggerConfigInterface;

#[Configuration(LoggerConfigInterface::class)]
final class LoggerConfig implements LoggerConfigInterface
{
	public array $default = [
		'handler'   => [
			'class'       => StreamHandler::class,
			'constructor' => [
				'stream' => 'logs/default.log',
				'level'  => Level::Debug,
			],
		],
		'formatter' => [
			'class'       => LineFormatter::class,
			'constructor' => [
				'format'                => null,
				'dateFormat'            => null,
				'allowInlineLineBreaks' => true,
			],
		],
	];
}
```

### 多 `handler` 配置

```php
<?php
declare(strict_types=1);

namespace Src\Config\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SuperKernel\Attribute\Configuration;
use SuperKernel\Logger\Contract\LoggerConfigInterface;

#[Configuration(LoggerConfigInterface::class)]
final class LoggerConfig implements LoggerConfigInterface
{
	public array $default = [
		[
		    'handler'   => [
			    'class'       => StreamHandler::class,
			    'constructor' => [
				    'stream' => 'logs/default.log',
				    'level'  => Level::Debug,
			    ],
		    ],
		    'formatter' => [
			    'class'       => LineFormatter::class,
			    'constructor' => [
				    'format'                => null,
				    'dateFormat'            => null,
				    'allowInlineLineBreaks' => true,
			    ],
		    ],
		],
		[
		    'handler'   => [
			    'class'       => StreamHandler::class,
			    'constructor' => [
				    'stream' => 'logs/default.log',
				    'level'  => Level::Debug,
			    ],
		    ],
		    'formatter' => [
			    'class'       => LineFormatter::class,
			    'constructor' => [
				    'format'                => null,
				    'dateFormat'            => null,
				    'allowInlineLineBreaks' => true,
			    ],
		    ],
		],
	];
}
```

## 🚀 示例

### 标准输出日志

```php
<?php
declare(strict_types=1);

namespace Src\Service;

use Psr\Log\LoggerInterface;

final class DemoService
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }
    
    public function method() 
    {
        $this->logger->info('Application started');
        $this->logger->error('Something went wrong');
    }
}
```

### 标准输出日志

```php
<?php
declare(strict_types=1);

namespace Src\Service;

use Psr\Log\LoggerInterface;

final class DemoService
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerFactoryInterface $loggerFactory)
    {
        $this->logger = $loggerFactory->get('default');
    }
    
    public function method()
    {
        $this->logger->info('Application started');
        $this->logger->error('Something went wrong');
    }
}
```

## 进阶

框架组件所输出的日志是由标准输出日志，我们认为此类日志输出一般无需记录，但开发者若存在此类需求可以考虑以下方式：

### nohup

```bash
nohup your_command > output.log 2>&1 &
```

退出终端后，进程依然在后台运行，标准输出日志将写入 output.log。

### setsid

```bash
setsid your_command > output.log 2>&1 &
```

与 nohup 类似，但不会继承原终端的会话 ID，更彻底地脱离控制终端。

### 重写`LoggerInterface`类提供者

```php
<?php
declare(strict_types=1);

namespace Src\Provider;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use Symfony\Component\Console\Output\ConsoleOutput;

#[
	Provider(class: LoggerInterface::class, priority: 2),
	Factory,
]
final class Logger implements LoggerInterface
{
    public function __invoke(LoggerFactoryInterface $loggerFactory): LoggerInterface
    {
        return $loggerFactory->get('your logger name');
    }
}
```

## 更多用法

请访问 [monolog/monolog](https://github.com/Seldaek/monolog) 以了解更多。