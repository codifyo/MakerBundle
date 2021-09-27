<?php

namespace Codifyo\MakerBundle\Utils;

class ConfigRoutes
{
    private $configDir;

    /**
     * @param $configDir
     */
    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    public function getRouteFile(): string
    {
        return $this->configDir.'/routes.yaml';
    }

    public function addDirAsResource(string $filename, string $name, string $dir, string $prefix, $type = 'annotation')
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(sprintf('The file %s does not exists.', $filename));
        }

        $file = fopen($filename, 'a');
        fwrite($file, PHP_EOL);
        fwrite($file, $name.':'.PHP_EOL);
        fwrite($file, '  resource: "'.$dir.'"'.PHP_EOL);
        fwrite($file, '  type: '.$type.PHP_EOL);
        fwrite($file, '  prefix: '.$prefix.PHP_EOL);
        fwrite($file, PHP_EOL);
        fclose($file);
    }
}