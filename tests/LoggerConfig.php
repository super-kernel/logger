<?php
declare(strict_types=1);

namespace SuperKernelTest\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SuperKernel\Attribute\Configuration;
use SuperKernel\Logger\Contract\LoggerConfigInterface;

#[Configuration(LoggerConfigInterface::class)]
final class LoggerConfig implements LoggerConfigInterface
{
	public array $default = [
		'handler'   => [
			'class'       => StreamHandler::class,
			'constructor' => [
				'stream' => 'logs/default.log',
				'level'  => Level::Debug,
			],
		],
		'formatter' => [
			'class'       => LineFormatter::class,
			'constructor' => [
				'format'                => null,
				'dateFormat'            => null,
				'allowInlineLineBreaks' => true,
			],
		],
	];
}