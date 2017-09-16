<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\QueueManager;

/**
 * Class ListCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class ListCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:list')
            ->addOption(
                'prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Returns queues with a name that begins with the specified value.',
                ''
            )
            ->setDescription('Returns a list of your queues.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Start getting the list of existing queues in SQS');

        /** @var QueueManager $queueManager */
        $queueManager = $this->getContainer()->get('TechnikaIt.sqs.queue_manager');
        $result = $queueManager->listQueue($input->getOption('prefix'));

        if (empty($result)) {
            $io->text('No queues found.');
        } else {
            $io->table(['Queue URL'], array_map(function ($value) {
                return [$value];
            }, $result));
        }

        $io->success('Done');
    }
}
