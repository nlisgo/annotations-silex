<?php

namespace eLife\Annotations\Command;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class QueueCreateCommand extends Command
{
    private $sqsClient;
    private $logger;

    public function __construct(SqsClient $sqsClient, LoggerInterface $logger, $queueName = null, $region = null)
    {
        parent::__construct(null);

        $this->sqsClient = $sqsClient;
        $this->logger = $logger;

        $this->addArgument('queueName', (!empty($queueName)) ? InputArgument::OPTIONAL : InputArgument::REQUIRED, '', $queueName);
        $this->addOption('region', 'r', InputOption::VALUE_OPTIONAL, '', $region);
    }

    protected function configure()
    {
        $this
            ->setName('queue:create')
            ->setDescription('Creates queue [development-only]');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $args = [
            'Region' => $input->getOption('region'),
            'QueueName' => $input->getArgument('queueName'),
        ];

        $io = new SymfonyStyle($input, $output);
        $in_region = (!empty($args['Region'])) ? ' ('.$args['Region'].')' : '';
        try {
            $this->sqsClient->getQueueUrl($args);
            $message = sprintf('Queue "%s"%s already exists.', $args['QueueName'], $in_region);
            $this->logger->warning($message);
            $io->warning($message);
        } catch (SqsException $exception) {
            $this->sqsClient->createQueue($args);
            $message = sprintf('Queue "%s"%s created successfully.', $args['QueueName'], $in_region);
            $this->logger->info($message);
            $io->success($message);
        }
    }
}
