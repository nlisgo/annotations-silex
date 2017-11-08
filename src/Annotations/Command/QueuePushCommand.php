<?php

namespace eLife\Annotations\Command;

use eLife\Bus\Queue\InternalSqsMessageFactory;
use eLife\Bus\Queue\WatchableQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class QueuePushCommand extends Command
{
    private $queue;
    private $messageFactory;
    private $logger;

    public function __construct(WatchableQueue $queue, InternalSqsMessageFactory $messageFactory, LoggerInterface $logger, $type = null)
    {
        parent::__construct(null);

        $this->queue = $queue;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->addArgument('type', !empty($type) ? InputArgument::OPTIONAL : InputArgument::REQUIRED, '', $type);
    }

    protected function configure()
    {
        $this
            ->setName('queue:push')
            ->setDescription('Manually enqueue item into SQS.')
            ->addArgument('id', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $type = $input->getArgument('type');
        // Create queue item.
        $item = $this->messageFactory::create($type, $id);
        // Queue item.
        $this->queue->enqueue($item);

        $io = new SymfonyStyle($input, $output);
        $message = 'Item added to queue.';
        $this->logger->info($message);
        $io->success($message);
    }
}
