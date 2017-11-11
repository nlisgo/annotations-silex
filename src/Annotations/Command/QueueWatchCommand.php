<?php

namespace eLife\Annotations\Command;

use eLife\ApiSdk\Model\Profile;
use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Limit\Limit;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\HypothesisClient\ApiSdk;
use eLife\HypothesisClient\Model\User;
use eLife\Logging\Monitoring;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;

final class QueueWatchCommand extends QueueCommand
{
    private $hypothesisSdk;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        ApiSdk $hypothesisSdk,
        LoggerInterface $logger,
        Monitoring $monitoring,
        Limit $limit,
        bool $serializedTransform = false
    ) {
        parent::__construct($logger, $queue, $transformer, $monitoring, $limit, $serializedTransform);
        $this->hypothesisSdk = $hypothesisSdk;
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue');
    }

    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
        if ($entity instanceof Profile) {
            $id = $entity->getIdentifier()->getId();
            $emails = $entity->getEmailAddresses();
            $display_name = $entity->getDetails()->getPreferredName();
            if (count($emails) > 0) {
                $email = $emails[0];
            } else {
                $this->logger->info(sprintf('No email address for profile "%s", backup email address created.', $id));
                $email = $id.'@hypothesis.elifesciences.org';
            }
            $user = new User($id, $email, $display_name);
            $store = $this->hypothesisSdk->users()->store($user)->wait();
            $this->logger->info(sprintf('Hypothesis user "%s" successfully %s.', $store->getId(), ($store->isNew() ? 'created' : 'updated')));
        }
    }
}
