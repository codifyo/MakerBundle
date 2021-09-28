<?php

namespace Codifyo\MakerBundle\DependencyInjection;

use Codifyo\MakerBundle\Generator\Generator;
use Codifyo\MakerBundle\Maker\DecoratingMaker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;

class MakerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definitions = $container->findTaggedServiceIds('maker.command');

        foreach ($definitions as $key => $def) {
            if (!$container->getDefinition($key)->hasTag('maker.command.no_bundle')) {
                $def = new Definition(DecoratingMaker::class, [
                    new Reference(".inner"),
                    new Reference(ParameterBagInterface::class)
                ]);
                $def->setDecoratedService($key);
                $container->setDefinition('myrh.maker.decorating.'.$key, $def);
            }
        }

        $generator = 'maker.generator';
        if ($container->hasDefinition($generator)) {
            $generatorDef = $container->getDefinition($generator);
            $generatorDef->setClass(Generator::class);
        }
    }
}