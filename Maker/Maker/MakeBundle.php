<?php

namespace Codifyo\MakerBundle\Maker\Maker;

use Codifyo\MakerBundle\Utils\ConfigBundle;
use Codifyo\MakerBundle\Utils\ConfigRoutes;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use function Symfony\Component\String\u;

class MakeBundle extends AbstractNoBundleMaker
{
    /**
     * @var ConfigBundle
     */
    private $configBundle;
    /**
     * @var ConfigRoutes
     */
    private $configRoutes;

    /**
     * @param ConfigBundle $configBundle
     */
    public function __construct(ConfigBundle $configBundle, ConfigRoutes $configRoutes)
    {
        $this->configBundle = $configBundle;
        $this->configRoutes = $configRoutes;
    }

    public static function getCommandName(): string
    {
        return 'make:bundle';
    }

    public static function getCommandDescription(): string
    {
        return 'Make a new bundle';
    }


    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->addArgument('bundle-name', InputArgument::OPTIONAL, sprintf('Name of the bundle (e.g. <fg=yellow>%s:%sBundle</>)', Str::asClassName(Str::getRandomTerm()), Str::asClassName(Str::getRandomTerm())))
//            ->addOption('config_root_name', InputOption::VALUE_OPTIONAL, sprintf("Name of the root nome for the bundle configuration (e.g <fg=yellow>%s</>)", Str::asSnakeCase(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../../Resources/help/MakeBundle.txt'))
        ;
    }

    /**
     * @param InputInterface $input
     * @param ConsoleStyle $io
     * @param \Codifyo\MakerBundle\Generator\Generator $generator
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        list($bundlePath, $bundleName) = $this->parseBundleName($input->getArgument('bundle-name'));
        $generator->setNamespacePrefix($bundlePath.'\\');

        $fullBundleNamespace = $this->generateMainBundleClass($io, $generator, $bundleName, $bundlePath);
        $this->generateDependencyInjectionClass($generator, $bundleName, $bundlePath);
        // create config file only if option "config" (config = main root name)
        // then create file in /package
        $this->generateConfig($generator, $bundleName, $bundlePath);
//
//        // todo if controller
        $this->generateController($generator, $bundleName, $bundlePath);
        $this->registerBundle($fullBundleNamespace);
        $this->registerRoutes($bundleName);
        // tests

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @param Generator $generator
     * @param $bundleName
     * @param $bundlePath
     * @throws \Exception
     */
    private function generateMainBundleClass(ConsoleStyle $io, Generator $generator, $bundleName, $bundlePath)
    {
        $bundleClassNameDetails = $generator->createClassNameDetails(
            $bundleName,
            '',
            'Bundle'
        );

        $generator->generateClass(
            $bundleClassNameDetails->getFullName(),
            'bundle/Bundle.tpl.php',
            []
        );

        return $bundleClassNameDetails->getFullName();
    }

    /**
     * @param Generator $generator
     * @param $bundleName
     * @param $bundlePath
     * @throws \Exception
     */
    private function generateDependencyInjectionClass(Generator $generator, $bundleName, $bundlePath)
    {
        $extensionClassNameDetails = $generator->createClassNameDetails(
            $bundleName,
            'DependencyInjection',
            'Extension'
        );

        $generator->generateClass(
            $extensionClassNameDetails->getFullName(),
            dirname(__DIR__, 2).'/Resources/skeleton/bundle/Extension.tpl.php',
            []
        );

        $configurationClassNameDetails = $generator->createClassNameDetails(
            'Configuration',
            'DependencyInjection',
            ''
        );

        $generator->generateClass(
            $configurationClassNameDetails->getFullName(),
            'bundle/Configuration.tpl.php',
            ['configuration_key' => u($bundleName)->snake()->toString()]
        );
    }

    public function generateConfig(Generator $generator, $bundleName, $bundlePath)
    {
        $generator->generateFile(
            $generator->getRootDirectory().'/src/'.str_replace('\\', '/', $bundlePath).'/Resources/config/services.yaml',
            'bundle/service.yaml.tpl.php',
            ['bundle_namespace' => $generator->getRootNamespace().'\\'.$bundlePath]
        );
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // TODO: Implement configureDependencies() method.
    }

    private function parseBundleName($bundleName)
    {
        $bundleDetails = explode(':', $bundleName);

        if (count($bundleDetails) > 2) {
            throw new \InvalidArgumentException('The bundle name is not valid');
        } elseif (count($bundleDetails) == 2) {
            $bundleName = $bundleDetails[1];
            $bundleGroupName = $bundleDetails[0];
        } else {
            $bundleName = $bundleDetails[0];
            $bundleGroupName = null;
        }

        if (!u($bundleName)->endsWith('Bundle')) {
            $bundleName .= 'Bundle';
        }

        return [
            ($bundleGroupName !== null) ? $bundleGroupName.'\\'.$bundleName : $bundleName,
            u($bundleGroupName.$bundleName)->replaceMatches('#(.*)Bundle$#', '$1')->toString(),
        ];

    }

    private function generateController(Generator $generator, $bundleName, $bundlePath)
    {
        $controllerClassNameDetails = $generator->createClassNameDetails(
            'Default',
            'Controller\\',
            'Controller'
        );

        $templateName = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()).'/index.html.twig';
        $controllerPath = $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'controller/Controller.tpl.php',
            [
                'route_path' => Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'route_name' => Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'with_template' => true,
                'template_name' => $templateName,
            ]
        );

        if (true) { // todo twig installed and withtemplate option
            $generator->generateTemplate(
                $templateName,
                'controller/twig_template.tpl.php',
                [
                    'controller_path' => $controllerPath,
                    'root_directory' => $generator->getRootDirectory(),
                    'class_name' => $controllerClassNameDetails->getShortName(),
                ]
            );
        }
    }

    private function registerBundle($bundleNamespace)
    {
        $bundlesFile = $this->configBundle->getConfFile();

        $bundles = $this->configBundle->load($bundlesFile);
        $bundles[$bundleNamespace] = ['all' => true];
        $this->configBundle->dump($bundlesFile, $bundles);
    }

    private function registerRoutes($bundleName)
    {
        $file = $this->configRoutes->getRouteFile();
        $this->configRoutes->addDirAsResource(
            $file,
            Str::asSnakeCase($bundleName),
            '@'.$bundleName.'Bundle/Controller/',
            '/'.Str::asSnakeCase($bundleName)
        );

//        $this->writeSuccessMessage()
    }
}