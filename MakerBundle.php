<?php

namespace App\Borh\MakerBundle;

use Codifyo\MakerBundle\DependencyInjection\MakerCompilerPass;
use Codifyo\MakerBundle\Maker\Maker\AbstractNoBundleMaker;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MakerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->registerForAutoconfiguration(AbstractNoBundleMaker::class)
            ->addTag('maker.command.no_bundle')
        ;

        $container->addCompilerPass(new MakerCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
