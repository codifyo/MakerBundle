<?php

namespace Codifyo\MakerBundle\Utils;

class ConfigBundle
{
    private $configDir;

    /**
     * @param $configDir
     */
    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    public function getConfFile(): string
    {
        return $this->configDir.'/bundles.php';
    }

    public function load(string $file): array
    {
        $bundles = file_exists($file) ? (require $file) : [];
        if (!\is_array($bundles)) {
            $bundles = [];
        }

        return $bundles;
    }

    public function dump(string $file, array $bundles)
    {
        $contents = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $contents .= "    $class::class => [";
            foreach ($envs as $env => $value) {
                $booleanValue = var_export($value, true);
                $contents .= "'$env' => $booleanValue, ";
            }
            $contents = substr($contents, 0, -2)."],\n";
        }
        $contents .= "];\n";

        if (!is_dir(\dirname($file))) {
            mkdir(\dirname($file), 0777, true);
        }

        file_put_contents($file, $contents);

        if (\function_exists('opcache_invalidate')) {
            opcache_invalidate($file);
        }
    }
}