<?php

namespace eLife\Annotations\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use Iterator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class QueueImportCommand extends Command
{
    private static $supports = ['profiles'];

    private $sdk;
    private $serializer;
    private $output;
    private $logger;
    private $monitoring;
    private $queue;
    private $limit;

    public function __construct(
        ApiSdk $sdk,
        WatchableQueue $queue,
        LoggerInterface $logger,
        Monitoring $monitoring,
        Limit $limit,
        $supports = []
    ) {
        parent::__construct(null);

        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
        $this->limit = $limit;
        $supports = array_intersect(self::$supports, (array) $supports);
        if (!empty($supports)) {
            self::$supports = $supports = array_intersect(self::$supports, $supports);
        }
        $this->addArgument('entity', (count(self::$supports) === 1) ? InputArgument::OPTIONAL : InputArgument::REQUIRED, 'Must be one of the following <comment>[all, '.implode(', ', self::$supports).']</comment>', (count(self::$supports) === 1) ? self::$supports[0] : null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:import')
            ->setDescription('Import items from API.')
            ->setHelp('Lists entities from API and enqueues them');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->output = $output;
        $entity = $input->getArgument('entity');
        // Only the configured.
        if ($entity !== 'all' && !in_array($entity, self::$supports)) {
            $message = sprintf('Entity with name "%s" not supported.', $entity);
            $this->logger->error($message);
            $io->error($message);
            throw new InvalidArgumentException($message);
        }

        try {
            $this->monitoring->nameTransaction($this->getName());
            $this->monitoring->startTransaction();
            $entities = ($entity === 'all') ? self::$supports : [$entity];
            foreach ($entities as $e) {
                $this->{'import'.ucfirst($e)}();
            }
            // Reporting.
            $message = 'All entities queued.';
            $this->logger->info($message);
            $io->success($message);
            $this->monitoring->endTransaction();
        } catch (Throwable $e) {
            $message = 'Error in import.';
            $this->logger->error($message, ['exception' => $e]);
            $this->monitoring->recordException($e, $message);
            $io->error($message);
            throw $e;
        }
    }

    public function importProfiles()
    {
        $this->logger->info('Importing Profiles.');
        $profiles = $this->sdk->profiles();
        $this->iterateSerializeTask($profiles, 'profile', 'getId', $profiles->count());
    }

    private function iterateSerializeTask(Iterator $items, string $type, $method = 'getId', int $count = 0)
    {
        $this->logger->info(sprintf('Importing %d item(s) of type "%s".', $count, $type));
        $progress = new ProgressBar($this->output, $count);
        $limit = $this->limit;

        $items->rewind();
        while ($items->valid()) {
            if ($limit()) {
                throw new RuntimeException(sprintf('Command cannot complete because: %s.', implode(', ', $limit->getReasons())));
            }
            $progress->advance();
            try {
                $item = $items->current();
                if ($item === null) {
                    $items->next();
                    continue;
                }
                $this->enqueue($type, $item->$method());
            } catch (Throwable $e) {
                $item = $item ?? null;
                $message = sprintf('Skipping import on a %s.', get_class($item));
                $this->logger->error($message, ['exception' => $e]);
                $this->monitoring->recordException($e, $message);
            }
            $items->next();
        }
        $progress->finish();
        $progress->clear();
    }

    private function enqueue($type, $identifier)
    {
        $this->logger->info(sprintf('Item (%s, %s) being enqueued.', $type, $identifier));
        $item = new InternalSqsMessage($type, $identifier);
        $this->queue->enqueue($item);
        $this->logger->info(sprintf('Item (%s, %s) enqueued successfully.', $type, $identifier));
    }
}
