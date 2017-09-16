<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\Queue;
use TechnikaIt\SqsBundle\Service\QueueManager;

/**
 * Class UpdateCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class UpdateCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:update')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            )
            ->setDescription('Update Queue attribute based on configuration');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $io->note('Option --force is mandatory to update data.');
            $io->warning('This action should not be used in the production environment.');

            return;
        }

        if (!$this->getContainer()->hasParameter('TechnikaIt.sqs.queues')) {
            $io->warning('Queue Configuration is missing.');

            return;
        }

        /** @var QueueManager $queueManager */
        $queueManager = $this->getContainer()->get('TechnikaIt.sqs.queue_manager');
        $awsQueues = $queueManager->listQueue();

        /** @var array $localQueues */
        $localQueues = $this->getContainer()->getParameter('TechnikaIt.sqs.queues');
        foreach ($localQueues as $queueName => $queueOption) {
            if (in_array($queueOption['queue_url'], $awsQueues, true)) {
                $io->text(sprintf('Updating queue <comment>%s</comment>', $queueOption['queue_url']));

                /** @var Queue $queue */
                $queue = $this->getContainer()->get(sprintf('TechnikaIt.sqs.%s', $queueName));
                $queueManager->setQueueAttributes($queue->getQueueUrl(), $queue->getAttributes());

                $io->table(['Attribute Name', 'Value'], array_map(function ($k, $v) {
                    return [$k, $v];
                }, array_keys($queue->getAttributes()), $queue->getAttributes()));
            }
        }

        $io->success('Success');
    }
}
