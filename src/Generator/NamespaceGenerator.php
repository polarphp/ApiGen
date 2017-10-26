<?php declare(strict_types=1);

namespace ApiGen\Generator;

use ApiGen\Configuration\Configuration;
use ApiGen\Contract\Generator\GeneratorInterface;
use ApiGen\Element\ReflectionCollector\NamespaceReflectionCollector;
use ApiGen\Templating\TemplateRenderer;

final class NamespaceGenerator implements GeneratorInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var TemplateRenderer
     */
    private $templateRenderer;

    /**
     * @var NamespaceReflectionCollector
     */
    private $namespaceReflectionCollector;

    private $constDefs = array();

    public function __construct(
        NamespaceReflectionCollector $namespaceReflectionCollector,
        Configuration $configuration,
        TemplateRenderer $templateRenderer
    ) {
        $this->namespaceReflectionCollector = $namespaceReflectionCollector;
        $this->configuration = $configuration;
        $this->templateRenderer = $templateRenderer;
    }

    public function generate(): void
    {
        foreach ($this->namespaceReflectionCollector->getNamespaces() as $namespace) {
            $this->generateForNamespace($namespace, $this->namespaceReflectionCollector);
        }
    }

    private function generateForNamespace(
        string $namespace,
        NamespaceReflectionCollector $namespaceReflectionCollector
    ): void {
            $parts = explode("\\", $namespace);
        if (count($parts) == 1) {
            $simpleNamespace = $parts[0];
        } else {
            $simpleNamespace = array_pop($parts);
        }
        $constants = $this->findConstByNamespace($namespace);
        $this->templateRenderer->renderToFile(
            $this->configuration->getTemplateByName('namespace'),
            $this->configuration->getDestinationWithPrefixName('namespace-', $namespace),
            [
                'apiCatalogKey' => 'namespaces',
                'activePage' => 'namespace',
                'simpleNamespace' => $simpleNamespace,
                'activeNamespace' => $namespace,
                'childNamespaces' => $this->resolveChildNamespaces($namespace),
                'classes' => $namespaceReflectionCollector->getClassReflections($namespace),
                'exceptions' => $namespaceReflectionCollector->getExceptionReflections($namespace),
                'interfaces' => $namespaceReflectionCollector->getInterfaceReflections($namespace),
                'traits' => $namespaceReflectionCollector->getTraitReflections($namespace),
                'functions' => $namespaceReflectionCollector->getFunctionReflections($namespace),
                'siteCategory' => $this->configuration->getOption('sitecategory'),
                'apiCatalog'=> $this->configuration->getOption('apicatalog'),
                "constants" => $constants
            ]
        );
    }

    private function findConstByNamespace($namespace)
    {
        $nsDir = getcwd().DIRECTORY_SEPARATOR."ConstDefs";
        if ($namespace != "none") {
            $nsDir .= DIRECTORY_SEPARATOR.$namespace;
        }
        $nsDir = str_replace("\\", DIRECTORY_SEPARATOR, $nsDir);
        $filename = $nsDir.DIRECTORY_SEPARATOR."ConstDefinitions.php";
        if (file_exists($filename)) {
            return include $filename;
        }
        return [];
    }

    /**
     * @return string[]
     */
    private function resolveChildNamespaces(string $namespace): array
    {
        $prefix = $namespace . '\\';
        $len = strlen($prefix);
        $namespaces = array();

        foreach ($this->namespaceReflectionCollector->getNamespaces() as $sub) {
            if (substr($sub, 0, $len) === $prefix
                && strpos(substr($sub, $len), '\\') === false
            ) {
                $namespaces[] = $sub;
            }
        }

        return $namespaces;
    }
}
