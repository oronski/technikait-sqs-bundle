<?php

namespace TechnikaIt\SqsBundle\Service;

use Aws\Sqs\SqsClient;
use TechnikaIt\SqsBundle\Service\Worker\AbstractWorker;

/**
 * Class QueueFactory
 * @package TechnikaIt\SqsBundle\Service
 */
class QueueFactory
{
    /**
     * @var Queue[]
     */
    private $queues;

    /**
     * @var SqsClient
     */
    private $client;

    /**
     * QueueFactory constructor.
     *
     * @param SqsClient $client
     */
    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param SqsClient $client
     * @param string $queueName
     * @param string $queueUrl
     * @param AbstractWorker $queueWorker
     * @param array $options
     *
     * @return Queue
     */
    public static function createQueue(
        SqsClient $client,
        string $queueName,
        string $queueUrl,
        AbstractWorker $queueWorker,
        array $options = []
    ) {
        $instance = new self($client);

        return $instance->create($queueName, $queueUrl, $queueWorker, $options);
    }

    /**
     * @param string $queueName
     * @param string $queueUrl
     * @param AbstractWorker $queueWorker
     * @param array $options
     *
     * @return Queue
     */
    public function create(
        string $queueName,
        string $queueUrl,
        AbstractWorker $queueWorker,
        array $options = []
    ) {
        if ($this->queues === null) {
            $this->queues = [];
        }

        if (isset($this->queues[$queueUrl])) {
            return $this->queues[$queueUrl];
        }

        $queue = new Queue($this->client, $queueName, $queueUrl, $queueWorker, $options);
        $this->queues[$queueUrl] = $queue;

        return $queue;
    }

    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient
    {
        return $this->client;
    }
}
