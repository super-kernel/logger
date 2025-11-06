<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

<div align="center">

# super-kernel/logger

**Log component of SuperKernel framework**

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-blue)](https://www.php.net/)
[![Monolog](https://img.shields.io/badge/monolog-^3.9-orange)](https://github.com/Seldaek/monolog)
[![SuperKernel](https://img.shields.io/badge/super--kernel-core-lightgrey)](https://github.com/super-kernel)

</div>

---

## Overview

`super-kernel/logger` is the official logging component of SuperKernel, providing two types of logging capabilities:

- **Standard Output Logging**: By default, it outputs to the terminal (`stdout`) via the `Logger` class, suitable for
  development and CLI environments.

- **Monolog Extended Logging**: Automatically creates developer-defined multichannel log instances via `LoggerFactory`,
  supporting various Handlers such as file, network, and queues.

This component conforms to the [psr/log](https://github.com/php-fig/log) standard and is seamlessly integrated with
SuperKernel's dependency injection system and configuration system.

---

## Installation

```bash
composer require super-kernel/logger
```

## Configuration

> This component only scans properties with `public` visibility in classes annotated with
`#[Configuration(LoggerConfigInterface::class)]`.

### Single `handler` configuration

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
        'handler' => [ 
            'class' => StreamHandler::class, 
            'constructor' => [ 
                'stream' => 'logs/default.log', 
                'level' => Level::Debug, 
            ], 
        ], 
        'formatter' => [ 
            'class' => LineFormatter::class, 
            'constructor' => [ 
                'format' => null, 
                'dateFormat' => null, 
                'allowInlineLineBreaks' => true, 
            ], 
        ], 
    ];
}
```

### Multiple `handler` configurations

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
            'handler' => [ 
                'class' => StreamHandler::class, 
                'constructor' => [ 
                        'stream' => 'logs/default.log', 
                        'level' => Level::Debug, 
                ], 
            ], 
            'formatter' => [ 
                'class' => LineFormatter::class, 
                'constructor' => [ 
                    'format' => null, 
                    'dateFormat' => null, 
                    'allowInlineLineBreaks' => true, 
                ], 
            ], 
        ], 
        [ 
            'handler' => [ 
                'class' => StreamHandler::class, 
                'constructor' => [ 
                    'stream' => 'logs/default.log', 
                    'level' => Level::Debug, 
                ], 
            ], 
            'formatter' => [ 
                'class' => LineFormatter::class, 
                'constructor' => [ 
                    'format' => null, 
                    'dateFormat' => null, 
                    'allowInlineLineBreaks' => true, 
                ], 
            ], 
        ], 
    ];
}
```

## 🚀 Example

### Standard output log

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

### Standard output log

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

## Advanced

The logs output by framework components are standard output logs. We generally believe that such log output does not
need to be recorded, but developers who have such needs can consider the following method:

### nohup

```bash
nohup your_command > output.log 2>&1 &

```

After exiting the terminal, the process continues to run in the background, and standard output logs will be written to
output.log.

### setsid

```bash
setsid your_command > output.log 2>&1 &

```

Similar to nohup, but it does not inherit the original terminal's session ID, thus more completely detaching from the
controlling terminal.

### Override the `LoggerInterface` class provider

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

## More usage

Please visit Visit [monolog/monolog](https://github.com/Seldaek/monolog) to learn more.