<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\Queue;

/**
 * Class ClearCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class ClearCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:clear')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Queue ID which you want to clear the message'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Set this parameter to execute this command'
            )
            ->setDescription('Clears the messages in a queue specified by the name parameter.');
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

        $name = $input->getArgument('name');
        if (!$this->getContainer()->has(sprintf('TechnikaIt.sqs.%s', $name)) {
            throw new \InvalidArgumentException(sprintf('Queue [%s] does not exist.', $name));
        }

        $io->title(sprintf('Start purge all your message in queue <comment>%s</comment>', $name));

        /** @var Queue $queue */
        $queue = $this->getContainer()->get(sprintf('TechnikaIt.sqs.%s', $name));
        $queue->purge();

        $io->text('All message in your specified queue were cleared successfully');
        $io->success('Done');
    }
}
