<?php declare(strict_types=1);

namespace ApiGen\Generator;

use ApiGen\Configuration\Configuration;
use ApiGen\Contract\Generator\GeneratorInterface;
use ApiGen\Templating\TemplateRenderer;
use ApiGen\Element\ReflectionCollector\NamespaceReflectionCollector;

final class GlobalsGenerator implements GeneratorInterface
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

    public function __construct(
        NamespaceReflectionCollector $namespaceReflectionCollector,
        Configuration $configuration,
        TemplateRenderer $templateRenderer
    )
    {
        $this->namespaceReflectionCollector = $namespaceReflectionCollector;
        $this->configuration = $configuration;
        $this->templateRenderer = $templateRenderer;
    }

    public function generate(): void
    {
        $nsDir = getcwd().DIRECTORY_SEPARATOR."ConstDefs";
        $nsDir = str_replace("\\", DIRECTORY_SEPARATOR, $nsDir);
        $filename = $nsDir.DIRECTORY_SEPARATOR."ConstDefinitions.php";
        $constants = [];
        if (file_exists($filename)) {
            $constants = include $filename;
        }
        $this->templateRenderer->renderToFile(
            $this->configuration->getTemplateByName('globals'),
            $this->configuration->getDestinationWithName('globals'),
            [
                'activePage' => 'globals',
                'classes' => $this->namespaceReflectionCollector->getClassReflections("none"),
                'interfaces' => $this->namespaceReflectionCollector->getInterfaceReflections("none"),
                'traits' => $this->namespaceReflectionCollector->getTraitReflections("none"),
                'functions' => $this->namespaceReflectionCollector->getFunctionReflections("none"),
                "constants" => $constants,
                'siteCategory' => $this->configuration->getOption('sitecategory'),
                'apiCatalog'=> $this->configuration->getOption('apicatalog')
            ]
        );
    }
}
