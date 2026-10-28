<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Tests\Unit;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use SuperKernel\Logger\Factory\LoggerFactory;
use Throwable;

use function file_get_contents;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class LoggerFactoryTest extends TestCase
{
    private ?string $logPath = null;

    protected function tearDown(): void
    {
        if (null !== $this->logPath && is_file($this->logPath)) {
            unlink($this->logPath);
        }

        $this->logPath = null;
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testDeclaresTheLoggerFactoryContract(): void
    {
        $factory = new ReflectionClass(LoggerFactory::class);
        $constructor = $factory->getConstructor();
        $create = $factory->getMethod('create');

        self::assertTrue($factory->isFinal());
        self::assertTrue($factory->isReadOnly());
        self::assertNull($constructor);
        self::assertTrue($create->isPublic());
        self::assertFalse($create->isStatic());
        self::assertSame(LoggerFactory::class, $create->getDeclaringClass()->getName());
        self::assertSame(2, $create->getNumberOfRequiredParameters());
        self::assertSame(4, $create->getNumberOfParameters());
        self::assertSame(LogLevel::INFO, $create->getParameters()[2]->getDefaultValue());
        self::assertSame('?' . FormatterInterface::class, (string) $create->getParameters()[3]->getType());
        self::assertNull($create->getParameters()[3]->getDefaultValue());
        self::assertSame(LoggerInterface::class, (string) $create->getReturnType());
    }

    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testCreatesTheConfiguredMonologLogger(): void
    {
        $logger = new LoggerFactory()->create('super-kernel', 'php://stderr');

        self::assertInstanceOf(LoggerInterface::class, $logger);
        self::assertInstanceOf(Logger::class, $logger);
        self::assertSame('super-kernel', $logger->getName());
        self::assertCount(1, $logger->getHandlers());

        $handler = $logger->getHandlers()[0];

        self::assertInstanceOf(StreamHandler::class, $handler);
        self::assertSame('php://stderr', $handler->getUrl());
        self::assertSame(Level::Info, $handler->getLevel());

        $logger->close();
    }

    public function testAppliesTheSelectedFormatterToTheStreamHandler(): void
    {
        $formatter = new LineFormatter('%message%');
        $logger = new LoggerFactory()->create(
            channel: 'console',
            stream: 'php://memory',
            formatter: $formatter,
        );
        self::assertInstanceOf(Logger::class, $logger);

        $handler = $logger->getHandlers()[0];
        self::assertInstanceOf(StreamHandler::class, $handler);
        self::assertSame($formatter, $handler->getFormatter());

        $logger->close();
    }

    public function testWritesOnlyRecordsAtOrAboveTheConfiguredLevel(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'super-kernel-logger-');
        self::assertNotFalse($path);
        $this->logPath = $path;

        $logger = new LoggerFactory()->create('audit', $path, LogLevel::WARNING);
        self::assertInstanceOf(Logger::class, $logger);

        $logger->info('Skipped {id}', [
            'id' => 41,
        ]);
        $logger->warning('Accepted {id}', [
            'id' => 42,
        ]);
        $logger->close();

        $contents = file_get_contents($path);
        self::assertNotFalse($contents);
        self::assertStringNotContainsString('Skipped', $contents);
        self::assertStringContainsString('audit.WARNING', $contents);
        self::assertStringContainsString('Accepted {id}', $contents);
        self::assertStringContainsString('"id":42', $contents);
    }

    public function testCreatesANewLoggerForEveryCall(): void
    {
        $factory = new LoggerFactory();

        $first = $factory->create('first', 'php://memory');
        $second = $factory->create('second', 'php://memory');

        self::assertInstanceOf(Logger::class, $first);
        self::assertInstanceOf(Logger::class, $second);
        self::assertNotSame($first, $second);

        $first->close();
        $second->close();
    }

    public function testRejectsAnUnknownMinimumLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LoggerFactory()->create('invalid', 'php://memory', 'unknown');
    }
}
