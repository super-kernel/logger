<?php
declare(strict_types=1);

namespace SuperKernel\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;
use SuperKernel\Attribute\Provider;
use Symfony\Component\Console\Output\ConsoleOutput;

#[
	Provider(LoggerInterface::class),
]
final class Logger implements LoggerInterface
{
	private ConsoleOutput $output;

	public function __construct()
	{
		$this->output = new ConsoleOutput();
	}

	public function emergency(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	public function log($level, Stringable|string $message, array $context = []): void
	{
		if (!empty($context)) {
			$message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
		}

		$line = match ($level) {
			        'error',
			        'critical',
			        'alert',
			        'emergency' => "<error>[ERROR] </error>",
			        'warning'   => "<comment>[WARNING] </comment>",
			        default     => "<info>[INFO] </info>",
		        } . $message;

		$this->output->writeln($line);
	}
}