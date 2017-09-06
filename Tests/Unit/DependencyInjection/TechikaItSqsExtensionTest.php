<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TechnikaIt\SqsBundle\DependencyInjection\TechnikaItSqsExtension;

/**
 * Class TechnikaItSqsExtensionTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\DependencyInjection
 */
class TechnikaItSqsExtensionTest extends TestCase
{
    /**
     * We customized the alias of Bundle, so we have to make sure it works as expected
     */
    public function testGetAlias()
    {
        $extension = new TechnikaItSqsExtension();
        $this->assertEquals('TechnikaIt_sqs', $extension->getAlias());
    }

    /**
     * @return ContainerBuilder
     */
    protected function getContainer(): ContainerBuilder
    {
        return new ContainerBuilder();
    }

    /**
     * Make sure the extension loaded all pre-defined services successfully
     */
    public function testPredefinedServicesLoaded()
    {
        $container = $this->getContainer();
        $extension = new TechnikaItSqsExtension();
        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('TechnikaIt.sqs.queue_factory'));
        $this->assertTrue($container->hasDefinition('TechnikaIt.sqs.queue_worker'));
        $this->assertTrue($container->hasDefinition('TechnikaIt.sqs.queue_manager'));
    }

    /**
     * Make sure the extension loaded all pre-defined parameters successfully via configuration
     */
    public function testPredefinedParametersLoaded()
    {
        $container = $this->getContainer();
        $extension = new TechnikaItSqsExtension();
        $extension->load([
            'TechnikaIt_sqs' => [
                'sqs' => [
                    'queues' => [
                        ['name' => 'queue-1', 'queue_url' => 'url-1', 'worker' => 'worker-1'],
                        ['name' => 'queue-2', 'queue_url' => 'url-2', 'worker' => 'worker-2'],
                    ]
                ]
            ]
        ], $container);

        $this->assertTrue($container->hasParameter('TechnikaIt.sqs.queues'));
    }
}
