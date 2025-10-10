<?php
declare(strict_types=1);

namespace SuperKernelTest;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use SuperKernel\Config\Attribute\Configuration;
use SuperKernel\Logger\Interface\LoggerInterface;

#[Configuration]
final class Logger implements LoggerInterface
{
	public function __invoke(): array
	{
		$default = new StreamHandler(
			stream: __DIR__ . '/../../../logs/default.log',
			level : Level::Debug,
		)->setFormatter(
			new LineFormatter(
				format               : null,
				dateFormat           : null,
				allowInlineLineBreaks: true,
			),
		);

		return compact('default');
	}
}