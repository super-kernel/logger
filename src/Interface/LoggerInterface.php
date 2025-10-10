<?php
declare(strict_types=1);

namespace SuperKernel\Logger\Interface;

use Monolog\Handler\HandlerInterface;

interface LoggerInterface
{
	/**
	 * @return array<HandlerInterface>
	 */
	public function __invoke(): array;
}