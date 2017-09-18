<?php

namespace TechnikaIt\SqsBundle\Tests\Functional\Command;

use TechnikaIt\SqsBundle\Command\ClearCommand;
use TechnikaIt\SqsBundle\Service\Queue;
use TechnikaIt\SqsBundle\Tests\app\KernelTestCase;

/**
 * Class QueueDeleteCommandTest
 * @package TechnikaIt\SqsBundle\Tests\Functional\Command
 */
class ClearCommandTest extends KernelTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Queue
     */
    private $queue;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        if ($this->queue === null) {
            $this->queue = $this->getMockBuilder(Queue::class)
                ->disableOriginalConstructor()
                ->getMock();
            $this->queue
                ->expects($this->any())
                ->method('purge')
                ->willReturn(true);

            $this->getContainer()->set('TechnikaIt.sqs.basic_queue', $this->queue);
        }
    }

    /**
     * Test: Purge a queue without force option
     */
    public function testExecuteWithoutForce()
    {
        $commandTester = $this->createCommandTester(new ClearCommand());
        $commandTester->execute([
            'name' => 'my-queue-name'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Option --force is mandatory to drop data', $output);
    }

    /**
     * Test: Purge a queue with a non-existing queue
     */
    public function testExecuteWithNonExistingQueue()
    {
        $commandTester = $this->createCommandTester(new ClearCommand());

        $this->expectException(\InvalidArgumentException::class);
        $commandTester->execute([
            'name' => 'non-existing-queue',
            '--force' => true
        ]);
    }

    /**
     * Test: Delete a queue without force option
     */
    public function testExecute()
    {
        $commandTester = $this->createCommandTester(new ClearCommand());
        $commandTester->execute([
            'name' => 'basic_queue',
            '--force' => true
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Done', $output);
    }
}
