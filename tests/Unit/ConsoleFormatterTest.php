<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Tests\Unit;

use DateTimeImmutable;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SuperKernel\Logger\Formatter\ConsoleFormatter;
use Throwable;

final class ConsoleFormatterTest extends TestCase
{
    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testDeclaresTheConsoleFormatterContract(): void
    {
        $formatter = new ReflectionClass(ConsoleFormatter::class);
        $constructor = $formatter->getConstructor();

        self::assertTrue($formatter->isFinal());
        self::assertTrue($formatter->isSubclassOf(LineFormatter::class));
        self::assertNotNull($constructor);
        self::assertSame(0, $constructor->getNumberOfRequiredParameters());
        self::assertCount(1, $constructor->getParameters());
        self::assertSame('bool', (string) $constructor->getParameters()[0]->getType());
        self::assertFalse($constructor->getParameters()[0]->getDefaultValue());
        self::assertFalse(new ConsoleFormatter()->isDecorated());
    }

    public function testFormatsARecordWithoutTrailingStructuredMetadata(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-08-18T03:05:46.007+00:00'),
            channel: 'super-kernel',
            level: Level::Notice,
            message: 'Swoole main server started.',
            context: [
                'mode' => 'base',
            ],
            extra: [
                'worker_id' => 7,
            ],
        );

        self::assertSame(
            '2026-08-18T03:05:46.007+00:00 [NOTICE] '
            . 'Swoole main server started.' . "\n",
            new ConsoleFormatter()->format($record),
        );
    }

    public function testOmitsEmptyMetadataWithoutLeavingTrailingWhitespace(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-08-18T03:05:46.007+00:00'),
            channel: 'super-kernel',
            level: Level::Info,
            message: 'Ready.',
        );

        self::assertSame(
            '2026-08-18T03:05:46.007+00:00 [INFO] Ready.' . "\n",
            new ConsoleFormatter()->format($record),
        );
    }

    public function testWrapsEveryPsrLevelInCompactBracketsWithOneFollowingSpace(): void
    {
        $formatter = new ConsoleFormatter();

        foreach (Level::cases() as $level) {
            $record = new LogRecord(
                datetime: new DateTimeImmutable('2026-08-18T03:05:46.007+00:00'),
                channel: 'super-kernel',
                level: $level,
                message: 'Level alignment.',
            );

            self::assertStringContainsString(
                ' [' . $level->getName() . '] Level alignment.',
                $formatter->format($record),
            );
        }
    }

    public function testDecoratesConsoleFieldsWithoutRenderingStructuredMetadata(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable('2026-08-18T03:05:46.007+00:00'),
            channel: 'super-kernel',
            level: Level::Notice,
            message: 'Swoole main server started.',
            context: [
                'mode' => 'base',
            ],
        );
        $formatter = new ConsoleFormatter(decorated: true);

        self::assertTrue($formatter->isDecorated());
        self::assertSame(
            "\033[2m2026-08-18T03:05:46.007+00:00\033[0m "
            . "\033[36m[NOTICE]\033[0m "
            . 'Swoole main server started.' . "\n",
            $formatter->format($record),
        );
    }

    public function testMapsEveryPsrLevelToAConsoleColor(): void
    {
        $formatter = new ConsoleFormatter(decorated: true);

        foreach ([
            [
                Level::Emergency,
                "\033[31m",
            ],
            [
                Level::Alert,
                "\033[31m",
            ],
            [
                Level::Critical,
                "\033[31m",
            ],
            [
                Level::Error,
                "\033[31m",
            ],
            [
                Level::Warning,
                "\033[33m",
            ],
            [
                Level::Notice,
                "\033[36m",
            ],
            [
                Level::Info,
                "\033[32m",
            ],
            [
                Level::Debug,
                "\033[32m",
            ],
        ] as [$level, $color]) {
            $record = new LogRecord(
                datetime: new DateTimeImmutable('2026-08-18T03:05:46.007+00:00'),
                channel: 'super-kernel',
                level: $level,
                message: 'Color mapping.',
            );

            self::assertStringContainsString(
                $color . '[' . $level->getName() . "]\033[0m",
                $formatter->format($record),
            );
        }
    }
}
