<?php
declare(strict_types=1);

namespace SuperKernel\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Contract\ConfigInterface;
use SuperKernel\Logger\Interface\LoggerInterface;

#[Factory]
final class LoggerFactory
{
	/**
	 * @var array<string,PsrLoggerInterface> $loggers
	 */
	private array $loggers = [];

	public function get(string $name): PsrLoggerInterface
	{
		return $this->loggers[$name];
	}

	public function __invoke(ConfigInterface $config): LoggerFactory
	{
		$handlers = $config->get(LoggerInterface::class)();

		foreach ($handlers as $name => $handler) {
			$this->loggers[$name] = new \Monolog\Logger($name)->pushHandler($handler);
		}

		return $this;
	}
}