<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Factory;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class LoggerFactory
{
    /**
     * @psalm-param LogLevel::* $minimumLevel
     *
     * @throws InvalidArgumentException When the minimum level is not a defined PSR-3 level
     */
    public function create(
        string              $channel,
        string              $stream,
        string              $minimumLevel = LogLevel::INFO,
        ?FormatterInterface $formatter = null,
    ): LoggerInterface
    {
        $handler = new StreamHandler($stream, $minimumLevel);

        if (null !== $formatter) {
            $handler->setFormatter($formatter);
        }

        return new Logger($channel, [
            $handler,
        ]);
    }
}
