<?php
declare(strict_types=1);

namespace SuperKernel\Logger\Contract;

use Psr\Log\LoggerInterface;

interface LoggerFactoryInterface
{
	public function get(string $name): LoggerInterface;
}