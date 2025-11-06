<?php
declare(strict_types=1);

namespace SuperKernel\Logger;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Psr\Log\LoggerInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ConfigInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Logger\Contract\LoggerConfigInterface;
use SuperKernel\Logger\Contract\LoggerFactoryInterface;
use function array_is_list;
use function get_object_vars;

#[
	Provider(LoggerFactoryInterface::class),
	Factory,
]
final class LoggerFactory implements LoggerFactoryInterface
{
	/**
	 * @var array<string, LoggerInterface> $loggers
	 */
	private array $loggers = [];

	public function __construct(private readonly ResolverFactoryInterface $resolverFactory)
	{
	}

	public function get(string $name): LoggerInterface
	{
		return $this->loggers[$name];
	}

	public function __invoke(ConfigInterface $config): LoggerFactory
	{
		$loggerConfig     = $config->get(LoggerConfigInterface::class);
		$publicProperties = get_object_vars($loggerConfig);

		foreach ($publicProperties as $name => $configs) {
			$logger = new \Monolog\Logger($name);

			if (!array_is_list($configs)) {
				$configs = [$configs];
			}

			foreach ($configs as $loggerConfig) {
				$handler = $this->createHandler($loggerConfig);
				$logger->pushHandler($handler);
			}

			$this->loggers[$name] = $logger;
		}


		return $this;
	}

	private function createHandler(array $config): HandlerInterface & FormattableHandlerInterface
	{
		$formatterClass  = $config['formatter']['class'];
		$formatterConfig = $config['formatter']['constructor'];
		$formatter       = $this->createFormatter($formatterClass, $formatterConfig);

		$handlerClass      = $config['handler']['class'];
		$handlerConfig     = $config['handler']['constructor'];
		$handlerDefinition = new ParameterDefinition($handlerClass, '__construct', $handlerConfig);

		$arguments = $this->resolverFactory->getResolver($handlerDefinition)->resolve($handlerDefinition);

		$handler = new $handlerClass(...$arguments);

		$handler->setFormatter($formatter);

		return $handler;
	}

	private function createFormatter(string $class, array $arguments): FormatterInterface
	{
		$definition = new ParameterDefinition($class, '__construct', $arguments);

		$arguments = $this->resolverFactory->getResolver($definition)->resolve($definition);

		return new $class(...$arguments);
	}
}