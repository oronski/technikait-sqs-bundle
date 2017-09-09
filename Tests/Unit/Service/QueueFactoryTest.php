<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\Service;

use Aws\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;
use TechnikaIt\SqsBundle\Service\Queue;
use TechnikaIt\SqsBundle\Service\QueueFactory;
use TechnikaIt\SqsBundle\Tests\app\Worker\BasicWorker;

/**
 * Class QueueFactoryTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\Service
 */
class QueueFactoryTest extends TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SqsClient
     */
    private function getAwsClient()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|SqsClient $client */
        $client = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $client;
    }

    /**
     * Test: Construction
     */
    public function testConstruction()
    {
        $client = $this->getAwsClient();
        $factory = new QueueFactory($client);

        $this->assertInstanceOf(QueueFactory::class, $factory);
        $this->assertEquals($client, $factory->getClient());
    }

    /**
     * Test: Create a new queue via Factory
     */
    public function testCreate()
    {
        $client = $this->getAwsClient();
        $worker = new BasicWorker();
        $factory = new QueueFactory($client);

        $queue = $factory->create('queue-name', 'queue-url', $worker);
        $this->assertInstanceOf(Queue::class, $queue);

        // Cached?
        $queue2 = $factory->create('queue-name', 'queue-url', $worker);
        $this->assertEquals($queue, $queue2);
    }

    /**
     * Test: Create a new queue via Factory
     */
    public function testCreateQueue()
    {
        $client = $this->getAwsClient();
        $this->assertInstanceOf(
            Queue::class,
            QueueFactory::createQueue($client, 'queue-name', 'queue-url', new BasicWorker())
        );
    }
}
