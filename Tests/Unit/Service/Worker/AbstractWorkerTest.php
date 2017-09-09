<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\Service\Worker;

use PHPUnit\Framework\TestCase;
use TechnikaIt\SqsBundle\Service\Message;
use TechnikaIt\SqsBundle\Service\Worker\AbstractWorker;

/**
 * Class AbstractWorkerTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\Service\Worker
 */
class AbstractWorkerTest extends TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractWorker
     */
    private function getAbstractWorker()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractWorker $client */
        $worker = $this->getMockBuilder(AbstractWorker::class)
            ->getMockForAbstractClass();

        return $worker;
    }


    /**
     * Test: Main processing of each worker with a ping pong message
     */
    public function testProcessWithMessage()
    {
        $message = (new Message())->setBody('my-message');

        $worker = $this->getAbstractWorker();
        $worker->expects($this->once())
            ->method('execute')
            ->with($message)
            ->willReturn(true);

        $result = $worker->process($message);
        $this->assertTrue($result);
    }

    /**
     * Test: Main processing of each worker in failure
     */
    public function testProcessInFailure()
    {
        $message = (new Message())->setBody('my-message');

        $worker = $this->getAbstractWorker();
        $worker->expects($this->once())
            ->method('execute')
            ->with($message)
            ->willThrowException(new \Exception());

        $result = $worker->process($message);
        $this->assertFalse($result);
    }
}
