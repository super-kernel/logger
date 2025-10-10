<?php
declare(strict_types=1);

namespace SuperKernelTest\Command;

use Psr\Log\LoggerInterface;
use SuperKernel\Logger\LoggerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('test')]
final class TestCommand extends Command
{
	public function __construct(private readonly LoggerInterface $logger, private readonly LoggerFactory $loggerFactory)
	{
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->logger->warning('logger');
		$this->loggerFactory->get('default')->debug('loggerFactory');

		return Command::SUCCESS;
	}
}