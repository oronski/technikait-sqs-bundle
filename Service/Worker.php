<?php

namespace TechnikaIt\SqsBundle\Service;

use Psr\Log\LoggerAwareTrait;

/**
 * Class Worker
 * @package TechnikaIt\SqsBundle\Service
 */
class Worker
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    private $consumed;

    /**
     * @param Queue $queue
     * @param int $amount
     * @param int $limit Zero is all
     */
    public function start(Queue $queue, int $amount = 0, int $limit = 1)
    {
        $this->consumed = 0;
        $this->consume($queue, $amount, $limit);
    }

    /**
     * @param Queue $queue
     * @param int $amount
     * @param int $limit
     */
    private function consume(Queue $queue, int $amount = 0, int $limit = 1)
    {
        while (true) {
            if ($amount && $this->consumed >= $amount) {
                break;
            }
            $this->fetchMessage($queue, $limit);
        }
    }

    /**
     * @param Queue $queue
     * @param int $limit
     */
    private function fetchMessage(Queue $queue, int $limit = 1)
    {
        $consumer = $queue->getQueueWorker();

        /** @var MessageCollection $result */
        $messages = $queue->receiveMessage($limit);

        $messages->rewind();
        while ($messages->valid()) {
            $this->consumed++;

            /** @var Message $message */
            $message = $messages->current();

            $this->logger && $this->logger->info(sprintf('Processing message ID: %s', $message->getId()));
            $result = $consumer->process($message);

            if ($result !== false) {
                $this->logger && $this->logger->info(
                    sprintf('Successfully processed message ID: %s', $message->getId())
                );
                $queue->deleteMessage($message);
            } else {
                $this->logger && $this->logger->warning(
                    sprintf('Cannot process message ID: %s, will release it back to queue', $message->getId())
                );
                $queue->releaseMessage($message);
            }

            $messages->next();
        }
    }
}
