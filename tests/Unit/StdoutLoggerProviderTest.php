<?php

declare(strict_types=1);

namespace SuperKernel\Logger\Tests\Unit;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Logger\Factory\LoggerFactory;
use SuperKernel\Logger\Formatter\ConsoleFormatter;
use SuperKernel\Logger\Provider\StdoutLoggerProvider;
use SuperKernel\Logger\StdoutLogger;
use Throwable;

use function defined;
use function getenv;
use function putenv;
use function stream_isatty;

use const STDOUT;

final class StdoutLoggerProviderTest extends TestCase
{
    public function testDeclaresTheNamedFactoryProviderContract(): void
    {
        $provider = new ReflectionClass(StdoutLoggerProvider::class);
        $attributes = $provider->getAttributes(FactoryProvider::class);
        $constructor = $provider->getConstructor();
        $invoke = $provider->getMethod('__invoke');

        self::assertTrue($provider->isFinal());
        self::assertTrue($provider->isReadOnly());
        self::assertCount(1, $attributes);
        self::assertSame(StdoutLoggerInterface::class, $attributes[0]->newInstance()->className);
        self::assertNull($constructor);
        self::assertTrue($invoke->isPublic());
        self::assertFalse($invoke->isStatic());
        self::assertSame(StdoutLoggerProvider::class, $invoke->getDeclaringClass()->getName());
        self::assertSame(1, $invoke->getNumberOfRequiredParameters());
        self::assertCount(1, $invoke->getParameters());
        self::assertSame(LoggerFactory::class, (string) $invoke->getParameters()[0]->getType());
        self::assertSame(StdoutLoggerInterface::class, (string) $invoke->getReturnType());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testCreatesAFreshStdoutLogger(): void
    {
        $provider = new StdoutLoggerProvider();
        $factory = new LoggerFactory();

        $first = $provider($factory);
        $second = $provider($factory);

        self::assertInstanceOf(StdoutLogger::class, $first);
        self::assertNotSame($first, $second);

        $delegate = new ReflectionClass(StdoutLogger::class)->getProperty('logger')->getValue($first);

        self::assertInstanceOf(Logger::class, $delegate);
        self::assertSame('super-kernel', $delegate->getName());
        self::assertCount(1, $delegate->getHandlers());

        $handler = $delegate->getHandlers()[0];

        self::assertInstanceOf(StreamHandler::class, $handler);
        self::assertSame('php://stdout', $handler->getUrl());
        self::assertSame(Level::Info, $handler->getLevel());
        $formatter = $handler->getFormatter();

        self::assertInstanceOf(ConsoleFormatter::class, $formatter);
        self::assertSame(
            false === getenv('NO_COLOR')
                && 'dumb' !== getenv('TERM')
                && defined('STDOUT')
                && stream_isatty(STDOUT),
            $formatter->isDecorated(),
        );

        $delegate->close();
        new ReflectionClass(StdoutLogger::class)->getProperty('logger')->getValue($second)->close();
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testNoColorDisablesDecoration(): void
    {
        $originalNoColor = getenv('NO_COLOR');
        self::assertTrue(putenv('NO_COLOR=1'));

        try {
            $logger = new StdoutLoggerProvider()(new LoggerFactory());
            $delegate = new ReflectionClass(StdoutLogger::class)->getProperty('logger')->getValue($logger);

            self::assertInstanceOf(Logger::class, $delegate);

            $handler = $delegate->getHandlers()[0];
            self::assertInstanceOf(StreamHandler::class, $handler);
            $formatter = $handler->getFormatter();

            self::assertInstanceOf(ConsoleFormatter::class, $formatter);
            self::assertFalse($formatter->isDecorated());

            $delegate->close();
        } finally {
            self::assertTrue(putenv(false === $originalNoColor ? 'NO_COLOR' : 'NO_COLOR=' . $originalNoColor));
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testDumbTerminalDisablesDecoration(): void
    {
        $originalTerm = getenv('TERM');
        self::assertTrue(putenv('TERM=dumb'));

        try {
            $logger = new StdoutLoggerProvider()(new LoggerFactory());
            $delegate = new ReflectionClass(StdoutLogger::class)->getProperty('logger')->getValue($logger);

            self::assertInstanceOf(Logger::class, $delegate);

            $handler = $delegate->getHandlers()[0];
            self::assertInstanceOf(StreamHandler::class, $handler);
            $formatter = $handler->getFormatter();

            self::assertInstanceOf(ConsoleFormatter::class, $formatter);
            self::assertFalse($formatter->isDecorated());

            $delegate->close();
        } finally {
            self::assertTrue(putenv(false === $originalTerm ? 'TERM' : 'TERM=' . $originalTerm));
        }
    }
}
