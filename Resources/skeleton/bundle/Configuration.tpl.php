<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
* This is the class that validates and merges configuration from your app/config files.
*
* To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
*/
class <?= $class_name; ?> implements ConfigurationInterface<?= "\n" ?>
{
    /**
    * {@inheritdoc}
    */
    public function getConfigTreeBuilder()
    {
        $rootNode = new TreeBuilder('<?= $configuration_key ?>');

        return $rootNode;
    }
}
