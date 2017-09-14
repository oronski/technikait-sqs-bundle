<?php

namespace TechnikaIt\SqsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TechnikaIt\SqsBundle\Service\QueueManager;

/**
 * Class AttrCommand
 * @package TechnikaIt\SqsBundle\Command
 */
class AttrCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('TechnikaIt:sqs:attr')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'SQS Queue Url'
            )
            ->setDescription('Retrieve the attribute of a specified queue url');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');

        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Start receiving the attributes for queue URL <comment>%s</comment>', $url));

        /** @var QueueManager $queueManager */
        $manager = $this->getContainer()->get('TechnikaIt.sqs.queue_manager');
        $result = $manager->getQueueAttributes($url);
        $io->table(['Attribute Name', 'Value'], array_map(function ($k, $v) {
            return [$k, $v];
        }, array_keys($result), $result));

        $io->text('Updated successfully');
        $io->success('Done');
    }
}
