<?php

namespace TechnikaIt\SqsBundle\Service;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use TechnikaIt\SqsBundle\Service\Worker\AbstractWorker;

/**
 * Class Queue
 * @package TechnikaIt\SqsBundle\Service
 */
class Queue
{
    /**
     * @var SqsClient
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $name;

    /**
     * @var AbstractWorker
     */
    private $worker;

    /**
     * @var array
     */
    private $attributes;

    /**
     * Queue constructor.
     *
     * @param SqsClient $client
     * @param string $name
     * @param string $url
     * @param AbstractWorker $worker
     * @param array $options
     */
    public function __construct(
        SqsClient $client,
        string $name,
        string $url,
        AbstractWorker $worker,
        array $options = []
    ) {
        $this->client = $client;
        $this->url = $url;
        $this->name = $name;
        $this->worker = $worker;
        $this->attributes = $options;
    }

    /**
     * @param Message $message
     * @param int $delay
     *
     * @return string
     */
    public function sendMessage(Message $message, int $delay = 0)
    {
        $params = [
            'DelaySeconds' => $delay,
            'MessageAttributes' => $message->getAttributes(),
            'MessageBody' => $message->getBody(),
            'url' => $this->url
        ];
        try {
            $result = $this->client->sendMessage($params);
            $messageId = $result->get('MessageId');
        } catch (AwsException $e) {
            throw new \InvalidArgumentException($e->getAwsErrorMessage());
        }

        return $messageId;
    }

    /**
     * Retrieves one or more messages (up to 10), from the specified queue.
     *
     * @param int $limit
     *
     * @return MessageCollection|Message[]
     */
    public function receiveMessage(int $limit = 1)
    {
        $collection = new MessageCollection([]);

        try {
            $result = $this->client->receiveMessage([
                'AttributeNames' => ['All'],
                'MaxNumberOfMessages' => $limit,
                'MessageAttributeNames' => ['All'],
                'url' => $this->url,
                'WaitTimeSeconds' => $this->attributes['ReceiveMessageWaitTimeSeconds'] ?? 0,
            ]);

            $messages = $result->get('Messages') ?? [];
            foreach ($messages as $message) {
                $collection->append(
                    (new Message())
                        ->setId($message['MessageId'])
                        ->setBody($message['Body'])
                        ->setReceiptHandle($message['ReceiptHandle'])
                        ->setAttributes($message['Attributes'])
                );
            }
        } catch (AwsException $e) {
            throw new \InvalidArgumentException($e->getAwsErrorMessage());
        }

        return $collection;
    }

    /**
     * Deletes the specified message from the specified queue
     *
     * @param Message $message
     *
     * @return bool
     */
    public function deleteMessage(Message $message)
    {
        try {
            $this->client->deleteMessage([
                'url' => $this->url,
                'ReceiptHandle' => $message->getReceiptHandle()
            ]);

            return true;
        } catch (AwsException $e) {
            throw new \InvalidArgumentException($e->getAwsErrorMessage());
        }
    }

    /**
     * Releases a message back to the queue, making it visible again
     *
     * @param Message $message
     *
     * @return bool
     */
    public function releaseMessage(Message $message)
    {
        try {
            $this->client->changeMessageVisibility([
                'url' => $this->url,
                'ReceiptHandle' => $message->getReceiptHandle(),
                'VisibilityTimeout' => 0
            ]);

            return true;
        } catch (AwsException $e) {
            throw new \InvalidArgumentException($e->getAwsErrorMessage());
        }
    }

    /**
     * Deletes the messages in a queue.
     * When you use the this action, you can't retrieve a message deleted from a queue.
     *
     * @return bool
     */
    public function purge()
    {
        try {
            $this->client->purgeQueue([
                'url' => $this->url
            ]);

            return true;
        } catch (AwsException $e) {
            throw new \InvalidArgumentException($e->getAwsErrorMessage());
        }
    }

    /**
     * @return string
     */
    public function geturl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function seturl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return AbstractWorker
     */
    public function getworker(): AbstractWorker
    {
        return $this->worker;
    }

    /**
     * @return string
     */
    public function getname(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient
    {
        return $this->client;
    }
}
