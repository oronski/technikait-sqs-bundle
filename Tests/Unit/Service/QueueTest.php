<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\Service;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;
use TechnikaIt\SqsBundle\Service\Queue;
use TechnikaIt\SqsBundle\Service\Message;
use TechnikaIt\SqsBundle\Service\MessageCollection;
use TechnikaIt\SqsBundle\Tests\app\Worker\BasicWorker;

/**
 * Class QueueTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\Service
 */
class QueueTest extends TestCase
{
    /**
     * @param array $entries
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Result
     */
    private function getAwsResult($entries)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Result $result */
        $result = $this->getMockBuilder(Result::class)->getMock();
        $result->expects($this->any())
            ->method('get')
            ->withAnyParameters()
            ->willReturnCallback(function ($arg) use ($entries) {
                return $entries[$arg] ?? [];
            });

        return $result;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsClient
     */
    private function getAwsClient()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|SqsClient $client */
        $client = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendMessage', 'receiveMessage', 'deleteMessage', 'changeMessageVisibility', 'purgeQueue'])
            ->getMock();

        return $client;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        $client = $this->getAwsClient();
        $name = 'queue-name';
        $url = 'queue-url';
        $worker = new BasicWorker();
        $queueAttr = ['a', 'b', 'c', 'd'];

        return new Queue($client, $name, $url, $worker, $queueAttr);
    }

    /**
     * Test: Construction
     */
    public function testConstruction()
    {
        $client = $this->getAwsClient();
        $name = 'queue-name';
        $url = 'queue-url';
        $worker = new BasicWorker();
        $queueAttr = ['a', 'b', 'c', 'd'];
        $queue = new Queue($client, $name, $url, $worker, $queueAttr);

        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertEquals($client, $queue->getClient());
        $this->assertEquals($name, $queue->getname());
        $this->assertEquals($url, $queue->geturl());
        $this->assertEquals($worker, $queue->getworker());
        $this->assertEquals($queueAttr, $queue->getAttributes());
    }

    /**
     * Test: Getter/Setter
     */
    public function testGetterSetter()
    {
        $client = $this->getAwsClient();
        $url = 'queue-url';
        $queueAttr = ['a', 'b', 'c', 'd'];

        $queue = new Queue($client, '', '', new BasicWorker(), []);

        $this->assertInstanceOf(Queue::class, $queue->seturl($url));
        $this->assertEquals($url, $queue->geturl());
        $this->assertInstanceOf(Queue::class, $queue->setAttributes($queueAttr));
        $this->assertEquals($queueAttr, $queue->getAttributes());
    }

    /**
     * Test: send message to a queue
     */
    public function testSendMessage()
    {
        $delay = random_int(1, 10);
        $messageBody = 'my-message';
        $messageAttr = ['x', 'y', 'z'];
        $url = 'queue-url';

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('sendMessage')
            ->with([
                'DelaySeconds' => $delay,
                'MessageAttributes' => $messageAttr,
                'MessageBody' => $messageBody,
                'url' => $url
            ])
            ->willReturn($this->getAwsResult(['MessageId' => 'new-message-id']));

        $queue = new Queue($client, 'queue-name', $url, new BasicWorker(), []);
        $this->assertEquals(
            'new-message-id',
            $queue->sendMessage(new Message($messageBody, $messageAttr), $delay)
        );
    }

    /**
     * Test: send message to a queue in failure
     */
    public function testSendMessageFailure()
    {
        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('sendMessage')
            ->withAnyParameters()
            ->willThrowException(new AwsException(
                'AWS Client Exception',
                new Command('send-message-command')
            ));

        $queue = new Queue($client, 'bad-queue-name', 'bad-queue-url', new BasicWorker(), []);

        $this->expectException(\InvalidArgumentException::class);
        $queue->sendMessage(new Message('my-message', []));
    }

    /**
     * Test: receive Message
     */
    public function testReceiveMessage()
    {
        $limit = random_int(1, 10);
        $url = 'queue-url';
        $queueAttr = ['ReceiveMessageWaitTimeSeconds' => random_int(1, 10)];
        $expected = [
            [
                'MessageId' => 'my-message-id',
                'Body' => 'my-body',
                'ReceiptHandle' => 'receipt-handle',
                'Attributes' => [],
            ]
        ];

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('receiveMessage')
            ->with([
                'AttributeNames' => ['All'],
                'MaxNumberOfMessages' => $limit,
                'MessageAttributeNames' => ['All'],
                'url' => $url,
                'WaitTimeSeconds' => $queueAttr['ReceiveMessageWaitTimeSeconds'],
            ])
            ->willReturn($this->getAwsResult(['Messages' => $expected]));

        $queue = new Queue($client, 'queue-name', $url, new BasicWorker(), $queueAttr);
        $result = $queue->receiveMessage($limit);
        $this->assertInstanceOf(MessageCollection::class, $result);
        $this->assertEquals($this->arrayMessageToCollection($expected), $result);
    }

    /**
     * @param array $messages
     *
     * @return MessageCollection
     */
    private function arrayMessageToCollection($messages)
    {
        $collection = new MessageCollection([]);
        foreach ($messages as $message) {
            $collection->append(
                (new Message())
                    ->setId($message['MessageId'])
                    ->setBody($message['Body'])
                    ->setReceiptHandle($message['ReceiptHandle'])
                    ->setAttributes($message['Attributes'])
            );
        }

        return $collection;
    }

    /**
     * Test: receive Message in failure
     */
    public function testReceiveMessageFailure()
    {
        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('receiveMessage')
            ->withAnyParameters()
            ->willThrowException(new AwsException(
                'AWS Client Exception',
                new Command('receive-message-command')
            ));

        $queue = new Queue($client, 'bad-queue-name', 'bad-queue-url', new BasicWorker(), []);
        $this->expectException(\InvalidArgumentException::class);
        $queue->receiveMessage();
    }

    /**
     * Test: Delete a message from queue
     */
    public function testDeleteMessage()
    {
        $url = 'queue-url';
        $message = (new Message())->setReceiptHandle('my-receipt-handle');

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('deleteMessage')
            ->with([
                'url' => $url,
                'ReceiptHandle' => $message->getReceiptHandle()
            ])
            ->willReturn(true);

        $queue = new Queue($client, 'queue-name', $url, new BasicWorker(), []);
        $this->assertTrue($queue->deleteMessage($message));
    }

    /**
     * Test: Delete a message from queue in failure
     */
    public function testDeleteMessageFailure()
    {
        $url = 'bad-queue-url';
        $message = (new Message())->setReceiptHandle('my-receipt-handle');

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('deleteMessage')
            ->with([
                'url' => $url,
                'ReceiptHandle' => $message->getReceiptHandle()
            ])
            ->willThrowException(new AwsException(
                'AWS Client Exception',
                new Command('delete-message-command')
            ));

        $queue = new Queue($client, 'bad-queue-name', $url, new BasicWorker(), []);
        $this->expectException(\InvalidArgumentException::class);
        $queue->deleteMessage($message);
    }

    /**
     * Test: Release a message from processing, making it visible again
     */
    public function testReleaseMessage()
    {
        $url = 'queue-url';
        $message = (new Message())->setReceiptHandle('my-receipt-handle');

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('changeMessageVisibility')
            ->with([
                'url' => $url,
                'ReceiptHandle' => $message->getReceiptHandle(),
                'VisibilityTimeout' => 0
            ])
            ->willReturn(true);

        $queue = new Queue($client, 'queue-name', $url, new BasicWorker(), []);
        $this->assertTrue($queue->releaseMessage($message));
    }

    /**
     * Test: Release a message from processing in failure
     */
    public function testReleaseMessageFailure()
    {
        $url = 'bad-queue-url';
        $message = (new Message())->setReceiptHandle('my-receipt-handle');

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('changeMessageVisibility')
            ->with([
                'url' => $url,
                'ReceiptHandle' => $message->getReceiptHandle(),
                'VisibilityTimeout' => 0
            ])
            ->willThrowException(new AwsException(
                'AWS Client Exception',
                new Command('release-message-command')
            ));

        $queue = new Queue($client, 'bad-queue-name', $url, new BasicWorker(), []);
        $this->expectException(\InvalidArgumentException::class);
        $queue->releaseMessage($message);
    }

    /**
     * Test: Delete a message from queue
     */
    public function testPurge()
    {
        $url = 'queue-url';

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('purgeQueue')
            ->with([
                'url' => $url
            ])
            ->willReturn(true);

        $queue = new Queue($client, 'queue-name', $url, new BasicWorker(), []);
        $this->assertTrue($queue->purge());
    }

    /**
     * Test: Delete a message from queue in failure
     */
    public function testPurgeFailure()
    {
        $url = 'bad-queue-url';

        $client = $this->getAwsClient();
        $client->expects($this->any())
            ->method('purgeQueue')
            ->with([
                'url' => $url
            ])
            ->willThrowException(new AwsException(
                'AWS Client Exception',
                new Command('delete-message-command')
            ));

        $queue = new Queue($client, 'bad-queue-name', $url, new BasicWorker(), []);
        $this->expectException(\InvalidArgumentException::class);
        $queue->purge();
    }
}
