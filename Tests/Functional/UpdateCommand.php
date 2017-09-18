<?php

namespace TechnikaIt\SqsBundle\Tests\Functional\Command;

use TechnikaIt\SqsBundle\Command\QueueUpdateCommand;
use TechnikaIt\SqsBundle\Service\QueueManager;
use TechnikaIt\SqsBundle\Tests\app\KernelTestCase;

/**
 * Class QueueUpdateCommandTest
 * @package TechnikaIt\SqsBundle\Tests\Functional\Command
 */
class UpdateCommandTest extends KernelTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QueueManager
     */
    private $queueManager;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        if ($this->queueManager === null) {
            $this->queueManager = $this->getMockBuilder(QueueManager::class)
                ->disableOriginalConstructor()
                ->getMock();
            $this->queueManager
                ->expects($this->any())
                ->method('listQueue')
                ->willReturn(['aws-basic-queue-url']);
            $this->queueManager
                ->expects($this->any())
                ->method('setQueueAttributes')
                ->willReturn(true);

            $this->getContainer()->set('TechnikaIt.sqs.queue_manager', $this->queueManager);
        }
    }

    /**
     * Test: Update Queue attribute based on configuration without force option
     */
    public function testExecuteWithoutForce()
    {
        $commandTester = $this->createCommandTester(new UpdateCommand());
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Option --force is mandatory to update data', $output);
    }

    /**
     * Test: Delete a queue without force option
     */
    public function testExecute()
    {
        $commandTester = $this->createCommandTester(new UpdateCommand());
        $commandTester->execute(['--force' => true]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Done', $output);
    }
}
