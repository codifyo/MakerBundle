<?php

namespace Codifyo\MakerBundle\Maker;


use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DecoratingMaker extends AbstractMaker
{
    /**
     * @var string[]
     */
    private $bundles;
    /**
     * @var AbstractMaker
     */
    private $maker;
    private static $commandName;
    private static $commandDescription;

    public function __construct(AbstractMaker $maker, ParameterBagInterface $parameterBag)
    {
        $this->maker = $maker;

        self::$commandName = $maker::getCommandName();
        self::$commandDescription = $maker::getCommandDescription();
        $this->bundles = $this->filterBundles($parameterBag->get('kernel.bundles'));
    }

    public static function getCommandName(): string
    {
        return self::$commandName;
    }

    public static function getCommandDescription(): string
    {
        return self::$commandDescription;
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $this->maker->configureCommand($command, $inputConfig);
        $command->addArgument(
                'bundle',
                InputArgument::OPTIONAL,
                sprintf('Choose the bundle where the command must be created (e.g. <fg=yellow>Acme:AcmeBundle</>)')
            )
            ->addOption('no-bundle', null, InputOption::VALUE_NONE, 'Run maker without any bundle')
        ;

        $inputConfig->setArgumentAsNonInteractive('bundle');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        parent::interact($input, $io, $command);

        if (!$input->getArgument('bundle') && !$input->getOption('no-bundle')) {
            $argument = $command->getDefinition()->getArgument('bundle');
            $question = $this->createBundleQuestion($argument->getDescription());
            $bundle = $io->askQuestion($question);
            $input->setArgument('bundle', $bundle);
        }
    }

    /**
     * @param InputInterface $input
     * @param ConsoleStyle $io
     * @param \Codifyo\MakerBundle\Generator\Generator $generator
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        if (!$input->getOption('no-bundle')) {
            $generator->setNamespacePrefix($this->getBundleNamespace($input->getArgument('bundle')));
        }

        $this->maker->generate($input, $io, $generator);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $this->maker->configureDependencies($dependencies);
    }


    /**
     * Remove bundle not located in App\ namespace
     * @param $bundles
     * @return array
     */
    private function filterBundles($bundles): array
    {
        $validBundles = [];

        foreach ($bundles as $bundle => $namespace) {
            if (0 === strpos($namespace, 'App\\')) {
                $validBundles[$bundle] = $namespace;
            }
        }

        return $validBundles;
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getBundleNamespace($bundle): string
    {
        $bundle = trim($bundle);

        if (!array_key_exists($bundle, $this->bundles)) {
            throw new \InvalidArgumentException(sprintf('Bundle %s does not exists', $bundle));
        }

        $bundleNamespace = $this->bundles[$bundle];
        $bundleNamespace = substr($bundleNamespace, 4);
        $bundleNamespace = substr($bundleNamespace, 0, strrpos($bundleNamespace, "\\") + 1);

        return $bundleNamespace;
    }

    private function createBundleQuestion($description)
    {
        $question = new Question($description);
        $question->setValidator(function($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('The bundle cannot be empty');
            }

            return $answer;
        });
        $question->setAutocompleterValues(array_keys($this->bundles));

        return $question;
    }
}
