<?php

namespace TechnikaIt\SqsBundle\Service\Worker;

use TechnikaIt\SqsBundle\Service\Message;

/**
 * Class AbstractWorker
 * @package TechnikaIt\SqsBundle\Service\Worker
 */
abstract class AbstractWorker
{
    /**
     * @param Message $message
     *
     * @return bool
     */
    final public function process(Message $message)
    {

        $this->preExecute($message);
        try {
            $result = $this->execute($message);
        } catch (\Exception $e) {
            return false;
        }
        $this->postExecute($message);

        return $result;
    }

    /**
     * @param Message $message
     */
    protected function preExecute(Message $message)
    {
        // Do something here
    }

    /**
     * @param Message $message
     */
    protected function postExecute(Message $message)
    {
        // Do something here
    }

    /**
     * @param Message $message
     *
     * @return boolean
     */
    abstract protected function execute(Message $message);
}
