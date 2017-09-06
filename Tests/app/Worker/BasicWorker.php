<?php

namespace TechnikaIt\SqsBundle\Tests\app\Worker;

use TechnikaIt\SqsBundle\Service\Message;
use TechnikaIt\SqsBundle\Service\Worker\AbstractWorker;

class BasicWorker extends AbstractWorker
{
    /**
     * @param Message $message
     *
     * @return boolean
     */
    protected function execute(Message $message)
    {
        return true;
    }
}