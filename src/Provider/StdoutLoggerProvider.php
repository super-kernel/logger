<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Provider;

use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Logger\Factory\LoggerFactory;
use SuperKernel\Logger\Formatter\ConsoleFormatter;
use SuperKernel\Logger\StdoutLogger;

use function defined;
use function getenv;
use function stream_isatty;

use const STDOUT;

#[FactoryProvider(StdoutLoggerInterface::class)]
final readonly class StdoutLoggerProvider
{
    public function __invoke(
        LoggerFactory $loggerFactory,
    ): StdoutLoggerInterface
    {
        $decorated = false === getenv('NO_COLOR')
            && 'dumb' !== getenv('TERM')
            && defined('STDOUT')
            && stream_isatty(STDOUT);

        return new StdoutLogger(
            $loggerFactory->create(
                channel: 'super-kernel',
                stream: 'php://stdout',
                formatter: new ConsoleFormatter(decorated: $decorated),
            ),
        );
    }
}
