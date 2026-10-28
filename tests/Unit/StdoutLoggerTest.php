<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use ReflectionClass;
use Stringable;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Logger\StdoutLogger;

final class StdoutLoggerTest extends TestCase
{
    public function testImplementsTheNamedLoggerContract(): void
    {
        $implementation = new ReflectionClass(StdoutLogger::class);
        $constructor = $implementation->getConstructor();

        self::assertTrue($implementation->isFinal());
        self::assertTrue($implementation->isReadOnly());
        self::assertTrue($implementation->implementsInterface(StdoutLoggerInterface::class));
        self::assertNotNull($constructor);
        self::assertSame(1, $constructor->getNumberOfRequiredParameters());
        self::assertSame(LoggerInterface::class, (string) $constructor->getParameters()[0]->getType());
    }

    public function testDelegatesPsrRecordsWithoutChangingTheirData(): void
    {
        $delegate = new class() implements LoggerInterface {
            use LoggerTrait;

            /** @var list<array{mixed, string|Stringable, array<array-key, mixed>}> */
            public array $records = [];

            /**
             * @param array<array-key, mixed> $context
             */
            public function log(mixed $level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    $level,
                    $message,
                    $context,
                ];
            }
        };
        $logger = new StdoutLogger($delegate);

        $logger->warning('Worker {workerId} stopped.', [
            'workerId' => 7,
        ]);

        self::assertSame([
            [
                LogLevel::WARNING,
                'Worker {workerId} stopped.',
                [
                    'workerId' => 7,
                ],
            ],
        ], $delegate->records);
    }
}
