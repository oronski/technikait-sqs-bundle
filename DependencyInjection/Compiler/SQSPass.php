<?php

namespace TechnikaIt\SqsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TechnikaIt\SqsBundle\Service\Queue;

/**
 * Class SQSPass
 * @package TechnikaIt\SqsBundle\DependencyInjection\Compiler
 */
class SQSPass implements CompilerPassInterface
{
    /**
     * Load configuration of Machine Engine
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('aws.sqs')) {
            throw new \InvalidArgumentException(
                'AWS SQSClient is required to use queue.'
            );
        }

        if ($container->hasParameter('TechnikaIt.sqs.queues')) {
            /** @var array $queues */
            $queues = $container->getParameter('TechnikaIt.sqs.queues');
            foreach ($queues as $queueName => $queueOption) {
                $defaultQueueServices = ['queues', 'queue_factory', 'queue_manager', 'queue_worker'];
                if (in_array($queueName, $defaultQueueServices, true)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid queue-name [%s]. Predefined queue-name: (%s)',
                        $queueName,
                        implode(', ', $defaultQueueServices)
                    ));
                }

                $queueOption['worker'] = preg_replace('/^@/', '', $queueOption['worker']);
                if ($container->has($queueOption['worker'])) {
                    $callable = new Reference($queueOption['worker']);
                } elseif (class_exists($queueOption['worker'])) {
                    $callable = new Definition($queueOption['worker']);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid worker of queue [%s]', $queueName)
                    );
                }

                $queueDefinition = new Definition(Queue::class);
                $queueDefinition
                    ->setFactory(
                        [
                            new Reference('TechnikaIt.sqs.queue_factory'),
                            'create'
                        ]
                    )
                    ->setPublic(true)
                    ->setArguments([
                        $queueName,
                        $queueOption['queue_url'],
                        $callable,
                        [
                            'DelaySeconds' =>
                                $queueOption['attributes']['delay_seconds'] ?? 0,
                            'MaximumMessageSize' =>
                                $queueOption['attributes']['maximum_message_size'] ?? 262144,
                            'MessageRetentionPeriod' =>
                                $queueOption['attributes']['message_retention_period'] ?? 345600,
                            'ReceiveMessageWaitTimeSeconds' =>
                                $queueOption['attributes']['receive_message_wait_time_seconds'] ?? 20,
                            'VisibilityTimeout' =>
                                $queueOption['attributes']['visibility_timeout'] ?? 30,
                            'RedrivePolicy' => !empty($queueOption['attributes']['redrive_policy']['dead_letter_queue'])
                                ? json_encode([
                                    'deadLetterTargetArn' =>
                                        $queueOption['attributes']['redrive_policy']['dead_letter_queue'] ?? '',
                                    'maxReceiveCount' =>
                                        $queueOption['attributes']['redrive_policy']['max_receive_count'] ?? 5,
                                ]) : ''
                        ]
                    ]);

                $queueId = sprintf('TechnikaIt.sqs.%s', $queueName);
                $container->setDefinition($queueId, $queueDefinition);
            }
        }
    }
}
