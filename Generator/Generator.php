<?php

namespace Codifyo\MakerBundle\Generator;

use Symfony\Bundle\MakerBundle\Generator as BaseGenerator;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

class Generator extends BaseGenerator
{
    private $namespacePrefix = '';

    public function setNamespacePrefix($namespacePrefix)
    {
        $this->namespacePrefix = $namespacePrefix;
    }
    public function createClassNameDetails(string $name, string $namespacePrefix, string $suffix = '', string $validationErrorMessage = ''): ClassNameDetails
    {
        $namespacePrefix = $this->namespacePrefix.$namespacePrefix;

        return parent::createClassNameDetails($name, $namespacePrefix, $suffix, $validationErrorMessage);
    }

    /**
     * Generate a template file.
     */
    public function generateTemplate(string $targetPath, string $templateName, array $variables = [])
    {
        $path = str_replace('\\', '/', 'src/'.$this->namespacePrefix.'Resources/views/');

//        dump($path.$targetPath);
//        die();
        $this->generateFile(
            $path.$targetPath,
            $templateName,
            $variables
        );
    }

    public function generateClass(string $className, string $templateName, array $variables = []): string
    {
        return parent::generateClass($className, $this->getTemplateName($templateName), array_merge($variables, ['bundle' => $this->getBundleKey()]));
    }

    public function generateFile(string $targetPath, string $templateName, array $variables = [])
    {
        parent::generateFile($targetPath, $this->getTemplateName($templateName), $variables);
    }

    private function getBundleKey() {
        $key = $this->namespacePrefix;
        $key = str_replace('\\', '', $key);
        $key = str_replace('/', '', $key);
        $key = str_replace('Bundle', '', $key);

        return $key;
    }

    private function getTemplateName($templateName): string
    {
        $templatePath = $templateName;
        if (!file_exists($templatePath)) {
            $templatePath = __DIR__.'/../Resources/skeleton/'.$templateName;

            if (!file_exists($templatePath)) {
                return $templateName;
            }
        }

        return $templatePath;
    }
}