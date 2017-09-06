<?php

namespace TechnikaIt\SqsBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use TechnikaIt\SqsBundle\DependencyInjection\Configuration;

/**
 * Class ConfigurationTest
 * @package TechnikaIt\SqsBundle\Tests\Unit\DependencyInjection
 */
class ConfigurationTest extends TestCase
{
    /**
     * Test Configuration Definition
     */
    public function testGetConfigTreeBuilder()
    {
        $processor = new Processor();
        $processorConfig = $processor->processConfiguration(new Configuration(), []);
        $expectedConfiguration = [];
        $this->assertEquals($expectedConfiguration, $processorConfig);
    }
}
