<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TechnikaIt\SqsBundle\DependencyInjection\Compiler\SQSPass;
use TechnikaIt\SqsBundle\Tests\app\Worker\BasicWorker;

/**
 * Class SQSQPass
 * @package TechnikaIt\SqsBundle\DependencyInjection\Compiler
 */
class SQSQPassTest extends TestCase
{
    /**
     * @return ContainerBuilder
     */
    protected function getContainer(): ContainerBuilder
    {
        return new ContainerBuilder();
    }

    /**
     * Make sure AWS SQS was loaded successfully
     */
    public function testAwsSqsLoadedFailure()
    {
        $container = $this->getContainer();
        $compiler = new SQSPass();

        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    /**
     * Make sure the worker of queue should be a valid and callable service.
     */
    public function testProcessFailureWithBadWorker()
    {
        $container = $this->getContainer();
        $container->setDefinition('aws.sqs', new Definition());
        $container->setParameter(
            'TechnikaIt.sqs.queues',
            ['queue-name' => ['queue_url' => 'my-url', 'worker' => 'bad-worker']]
        );

        $compiler = new SQSPass();
        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    /**
     * Make sure the name of queue should be different with predefined queue-name
     */
    public function testProcessFailureWithPredefinedQueueName()
    {
        $container = $this->getContainer();
        $container->setDefinition('aws.sqs', new Definition());

        $defaultQueueServices = ['queues', 'queue_factory', 'queue_manager', 'queue_worker', 'basic_queue'];
        $container->setParameter(
            'TechnikaIt.sqs.queues',
            [
                $defaultQueueServices[array_rand($defaultQueueServices)] => [
                    'queue_url' => 'my-url',
                    'worker' => 'bad-worker'
                ]
            ]
        );

        $compiler = new SQSPass();
        $this->expectException(\InvalidArgumentException::class);
        $compiler->process($container);
    }

    /**
     * @return array
     */
    public function configurationProvider(): array
    {
        $container = $this->getContainer();
        $container->setDefinition('aws.sqs', new Definition());

        $basicWorker = BasicWorker::class;
        $basicWorkerAsService = 'TechnikaIt.sqs.fixture.basic_worker';
        $container->setDefinition($basicWorkerAsService, new Definition($basicWorker));

        return [
            // Case #0: Load a worker with default attributes
            [
                $container,
                [
                    'basic-queue' => [
                        'queue_url' => 'basic-url',
                        'worker' => $basicWorker,
                        'attributes' => []
                    ]
                ],
                [
                    'basic-queue' => [
                        'basic-url',
                        new Definition($basicWorker),
                        [
                            'DelaySeconds' => 0,
                            'MaximumMessageSize' => 262144,
                            'MessageRetentionPeriod' => 345600,
                            'ReceiveMessageWaitTimeSeconds' => 20,
                            'VisibilityTimeout' => 30,
                            'RedrivePolicy' => ''
                        ]
                    ]
                ]
            ],
            // Case #1: Load a worker as a callable class with some attributes
            [
                $container,
                [
                    'basic-queue' => [
                        'queue_url' => 'basic-url',
                        'worker' => $basicWorker,
                        'attributes' => [
                            'delay_seconds' => 1,
                            'maximum_message_size' => 1,
                            'message_retention_period' => 1,
                            'receive_message_wait_time_seconds' => 1,
                            'visibility_timeout' => 1,
                            'redrive_policy' => [
                                'dead_letter_queue' => 'basic_dead_letter_queue_1',
                                'max_receive_count' => 1
                            ]
                        ]
                    ]
                ],
                [
                    'basic-queue' => [
                        'basic-url',
                        new Definition($basicWorker),
                        [
                            'DelaySeconds' => 1,
                            'MaximumMessageSize' => 1,
                            'MessageRetentionPeriod' => 1,
                            'ReceiveMessageWaitTimeSeconds' => 1,
                            'VisibilityTimeout' => 1,
                            'RedrivePolicy' => json_encode([
                                'deadLetterTargetArn' => 'basic_dead_letter_queue_1',
                                'maxReceiveCount' => 1
                            ])
                        ]
                    ]
                ]
            ],
            // Case #2: Load a worker as a service
            [
                $container,
                [
                    'basic-queue' => [
                        'queue_url' => 'basic-url',
                        'worker' => $basicWorkerAsService,
                        'attributes' => [
                            'delay_seconds' => 2,
                            'maximum_message_size' => 2,
                            'message_retention_period' => 2,
                            'receive_message_wait_time_seconds' => 2,
                            'visibility_timeout' => 2,
                            'redrive_policy' => [
                                'dead_letter_queue' => 'basic_dead_letter_queue_2',
                                'max_receive_count' => 2
                            ]
                        ]
                    ]
                ],
                [
                    'basic-queue' => [
                        'basic-url',
                        new Reference($basicWorkerAsService),
                        [
                            'DelaySeconds' => 2,
                            'MaximumMessageSize' => 2,
                            'MessageRetentionPeriod' => 2,
                            'ReceiveMessageWaitTimeSeconds' => 2,
                            'VisibilityTimeout' => 2,
                            'RedrivePolicy' => json_encode([
                                'deadLetterTargetArn' => 'basic_dead_letter_queue_2',
                                'maxReceiveCount' => 2
                            ])
                        ]
                    ]
                ]
            ],
            // Case #3: Load multi queues at the same time
            [
                $container,
                [
                    'basic-queue-1' => ['queue_url' => 'basic-url-1', 'worker' => $basicWorker],
                    'basic-queue-2' => ['queue_url' => 'basic-url-2', 'worker' => $basicWorkerAsService]
                ],
                [
                    'basic-queue-1' => [
                        'basic-url-1',
                        new Definition($basicWorker),
                        [
                            'DelaySeconds' => 0,
                            'MaximumMessageSize' => 262144,
                            'MessageRetentionPeriod' => 345600,
                            'ReceiveMessageWaitTimeSeconds' => 20,
                            'VisibilityTimeout' => 30,
                            'RedrivePolicy' => ''
                        ]
                    ],
                    'basic-queue-2' => [
                        'basic-url-2',
                        new Reference($basicWorkerAsService),
                        [
                            'DelaySeconds' => 0,
                            'MaximumMessageSize' => 262144,
                            'MessageRetentionPeriod' => 345600,
                            'ReceiveMessageWaitTimeSeconds' => 20,
                            'VisibilityTimeout' => 30,
                            'RedrivePolicy' => ''
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Load configuration of Machine Engine
     *
     * @param ContainerBuilder $container
     * @param array $config
     * @param array $expectedArgs
     *
     * @dataProvider configurationProvider
     */
    public function testProcess($container, $config, $expectedArgs)
    {
        $container->setParameter('TechnikaIt.sqs.queues', $config);

        $compiler = new SQSPass();
        $compiler->process($container);

        foreach ($config as $queueName => $queueOption) {
            $queueId = sprintf('TechnikaIt.sqs.%s', $queueName);

            $this->assertTrue($container->hasDefinition($queueId));

            $definition = $container->getDefinition($queueId);
            $this->assertEquals([
                new Reference('TechnikaIt.sqs.queue_factory'),
                'create'
            ], $definition->getFactory());

            $this->assertEquals(
                array_merge([$queueName], $expectedArgs[$queueName]),
                $definition->getArguments()
            );
        }
    }
}
