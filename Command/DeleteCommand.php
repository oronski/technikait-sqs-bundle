<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\QueueManager;

/**
 * Class DeleteCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class DeleteCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:delete')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Queue Url which you want to remove'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            )
            ->setDescription('Delete a queue by url and all its messages');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if (!$input->getOption('force')) {
            $io->note('Option --force is mandatory to drop data.');
            $io->warning('This action should not be used in the production environment.');
            return;
        }

        $queueUrl = $input->getArgument('url');

        $io->title(sprintf('Start deleting the specified queue by URL <comment>%s</comment>', $queueUrl));

        /** @var QueueManager $queueManager */
        $queueManager = $this->getContainer()->get('TechnikaIt.sqs.queue_manager');
        $queueManager->deleteQueue($queueUrl);

        $io->text('Success');
        $io->success('Done');
    }
}
