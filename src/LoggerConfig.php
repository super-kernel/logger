<?php
declare(strict_types=1);

namespace SuperKernel\Logger;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;

final readonly class LoggerConfig
{
    public function __construct(
        public AbstractProcessingHandler $handler,
        public FormatterInterface        $formatter,
    )
    {
    }
}