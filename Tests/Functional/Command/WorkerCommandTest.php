<?php

namespace TechnikaIt\SqsBundle\Tests\Functional\Command;

use TechnikaIt\SqsBundle\Command\WorkerCommand;
use TechnikaIt\SqsBundle\Service\Worker;
use TechnikaIt\SqsBundle\Tests\app\KernelTestCase;

/**
 * Class WorkerCommandTest
 * @package TechnikaIt\SqsBundle\Tests\Functional\Command
 */
class WorkerCommandTest extends KernelTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Worker
     */
    private $Worker;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        if ($this->Worker === null) {
            $this->Worker = $this->getMockBuilder(Worker::class)
                ->disableOriginalConstructor()
                ->getMock();
            $this->Worker
                ->expects($this->any())
                ->method('start')
                ->willReturn(true);

            $this->getContainer()->set('TechnikaIt.sqs.queue_worker', $this->Worker);
        }
    }

    /**
     * Test: start a worker for a non-existing queue
     */
    public function testExecuteWithNonExistingQueue()
    {
        $commandTester = $this->createCommandTester(new WorkerCommand());

        $this->expectException(\InvalidArgumentException::class);
        $commandTester->execute([
            'name' => 'non-existing-queue'
        ]);
    }

    /**
     * Test: start a worker with an invalid value of amount of messages
     */
    public function testExecuteWithInvalidAmountMessages()
    {
        $commandTester = $this->createCommandTester(new WorkerCommand());

        $this->expectException(\InvalidArgumentException::class);
        $commandTester->execute([
            'name' => 'basic_queue',
            '--messages' => -1
        ]);
    }

    /**
     * Test: Start a worker for listening to a queue
     */
    public function testExecute()
    {
        $commandTester = $this->createCommandTester(new WorkerCommand());
        $commandTester->execute([
            'name' => 'basic_queue'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Start listening', $output);
    }
}
