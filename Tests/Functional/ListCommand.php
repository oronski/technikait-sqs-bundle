<?php

namespace TechnikaIt\SqsBundle\Tests\Functional\Command;

use TechnikaIt\SqsBundle\Command\QueueListCommand;
use TechnikaIt\SqsBundle\Service\QueueManager;
use TechnikaIt\SqsBundle\Tests\app\KernelTestCase;

/**
 * Class QueueCreateCommandTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\Command
 */
class QueueListCommandTest extends KernelTestCase
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
                ->willReturnCallback(function ($arg) {
                    return $arg == 'invalid' ? [] : ['queue-url-1', 'queue-url-2'];
                });

            $this->getContainer()->set('TechnikaIt.sqs.queue_manager', $this->queueManager);
        }
    }

    /**
     * Test: Returns a list of your queues.
     */
    public function testExecute()
    {
        $commandTester = $this->createCommandTester(new ListCommand());
        $commandTester->execute([
            '--prefix' => 'queue-prefix'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('queue-url-1', $output);
        $this->assertContains('queue-url-2', $output);
        $this->assertContains('Done', $output);
    }

    /**
     * Test: There are not any queue
     */
    public function testExecuteWithEmpty()
    {
        $commandTester = $this->createCommandTester(new ListCommand());
        $commandTester->execute([
            '--prefix' => 'invalid'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains(
            'You don\'t have any queue at this moment. Please go to AWS Console to create a new one.',
            $output
        );
    }
}
