<?php

namespace Technikait\SqsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TechnikaIt\SqsBundle\DependencyInjection\Compiler\SQSPass;
use TechnikaIt\SqsBundle\DependencyInjection\TechnikaItSqsExtension;

/**
 * Class TTechnikaItSqsBundle
 * @package TechnikaIt\SqsBundle
 */
class TechnikaItSqsBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SQSPass());
    }

    /**
     * @inheritdoc
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new TechnikaItSqsExtension();
        }

        return $this->extension;
    }
}
