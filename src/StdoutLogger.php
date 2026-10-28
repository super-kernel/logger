<?php

declare(strict_types=1);

namespace SuperKernel\Logger;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use SuperKernel\Contract\StdoutLoggerInterface;

final readonly class StdoutLogger implements StdoutLoggerInterface
{
    use LoggerTrait;

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @throws InvalidArgumentException When the delegated logger rejects the supplied level
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
