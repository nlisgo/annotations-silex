<?php

namespace eLife\Annotations\Command;

use eLife\ApiSdk\Model\Profile;
use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\HypothesisClient\ApiSdk;
use eLife\HypothesisClient\Exception\ApiException;
use eLife\HypothesisClient\Exception\BadResponse;
use eLife\Logging\Monitoring;
use Exception;
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
        callable $limit,
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
            $user_client = $this->hypothesisSdk->createUsers();

            $id = $entity->getIdentifier()->getId();
            $display_name = $entity->getDetails()->getPreferredName();
            $emails = $entity->getEmailAddresses();
            $backup_email = $id.'@hypothesis.elifesciences.org';
            if (count($emails) > 0) {
                $email = $emails[0];
            } else {
                $this->logger->info(sprintf('No email address for profile "%s", backup email address created.', $id));
                $email = $backup_email;
            }

            $error_message = sprintf('Unable to modify hypothesis user "%s".', $id);
            try {
                // Try to create a user first.
                $user_client->createUser([], $id, $email, $display_name)->wait();
                $this->logger->info(sprintf('New hypothesis user created "%s".', $id));
            } catch (BadResponse $exception) {
                $body = (string) $exception->getResponse()->getBody();
                // Unable to create new, check if username is known.
                if (preg_match('/user with username [^\s]+ already exists/', $body)) {
                    try {
                        // Try to modify existing user.
                        $user_client->modifyUser([], $id, $email, $display_name)
                            ->wait();
                        $this->logger->info(
                            sprintf(
                                'Existing hypothesis user modified "%s".',
                                $id
                            )
                        );
                    } catch (BadResponse $exception) {
                        // Unable to modify user, check is email address is already associated with a different user.
                        if (preg_match(
                            '/user with email address [^\s]+ already exists/',
                            (string) $exception->getResponse()->getBody()
                        )) {
                            try {
                                // Try to modify user, with backup email address.
                                $user_client->modifyUser(
                                    [],
                                    $id,
                                    $backup_email,
                                    $display_name
                                )->wait();
                                $this->logger->info(
                                    sprintf(
                                        'Existing hypothesis user modified "%s" with backup email address.',
                                        $id
                                    )
                                );
                            } catch (ApiException $exception) {
                                $this->logger->error(
                                    $error_message,
                                    ['exception' => $exception]
                                );
                                throw $exception;
                            }
                        } else {
                            $this->logger->error(
                                $error_message,
                                ['exception' => $exception]
                            );
                            throw $exception;
                        }
                    }
                } elseif (preg_match('/user with email address [^\s]+ already exists/', $body)) {
                    try {
                        // Try to create a user, with backup email address.
                        $user_client->createUser([], $id, $backup_email, $display_name)->wait();
                        $this->logger->info(sprintf('New hypothesis user created "%s" with backup email address.', $id));
                    } catch (Exception $exception) {
                        $this->logger->error($error_message, ['exception' => $exception]);
                        throw $exception;
                    }
                } else {
                    $this->logger->error($error_message, ['exception' => $exception]);
                    throw $exception;
                }
            } catch (Exception $exception) {
                $this->logger->error($error_message, ['exception' => $exception]);
                throw $exception;
            }
        }
    }
}
