<?php declare(strict_types=1);

namespace ApiGen\Generator;

use ApiGen\Configuration\Configuration;
use ApiGen\Contract\Generator\GeneratorInterface;
use ApiGen\Reflection\Contract\Reflection\Class_\ClassReflectionInterface;
use ApiGen\Reflection\ReflectionStorage;
use ApiGen\SourceCodeHighlighter\SourceCodeHighlighter;
use ApiGen\Templating\TemplateRenderer;

final class ClassGenerator implements GeneratorInterface
{
    /**
     * @var ReflectionStorage
     */
    private $reflectionStorage;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SourceCodeHighlighter
     */
    private $sourceCodeHighlighter;

    /**
     * @var TemplateRenderer
     */
    private $templateRenderer;

    public function __construct(
        ReflectionStorage $reflectionStorage,
        Configuration $configuration,
        SourceCodeHighlighter $sourceCodeHighlighter,
        TemplateRenderer $templateRenderer
    ) {
        $this->reflectionStorage = $reflectionStorage;
        $this->configuration = $configuration;
        $this->sourceCodeHighlighter = $sourceCodeHighlighter;
        $this->templateRenderer = $templateRenderer;
    }

    public function generate(): void
    {
        foreach ($this->reflectionStorage->getClassReflections() as $classReflection) {
            $this->generateForClass($classReflection);
//            if ($classReflection->getFileName()) {
//                $this->generateSourceCodeForClass($classReflection);
//            }
        }
    }

    private function generateForClass(ClassReflectionInterface $classReflection): void
    {

        $entities = $this->separateEntities($classReflection);
        $this->templateRenderer->renderToFile(
            $this->configuration->getTemplateByName('class'),
            $this->configuration->getDestinationWithPrefixName('class-', $classReflection->getName()),
            [
                'apiCatalogKey' => 'classes',
                'activePage' => 'class',
                'class' => $classReflection,
                'siteCategory' => $this->configuration->getOption('sitecategory'),
                'apiCatalog'=> $this->configuration->getOption('apicatalog'),
                'constants' => $entities['consts'],
                'inheritConstants' => $entities['inheritConsts'],
                'properties' => $entities['properties'],
                'inheritProperties' => $entities['inheritProperties'],
                'methods' => $entities['methods'],
                'inheritMethods' => $entities['inheritMethods'],
                'traitProperties' => $entities['traitProperties'],
                'traitMethods' => $entities['traitMethods']
            ]
        );
    }

    private function separateEntities(ClassReflectionInterface $classReflection)
    {
        $ret = array(
            "consts" => array(
                "public" => array(),
                "protected" => array(),
                "private" => array()
            ),
            "inheritConsts" => array(
                "public" => array(),
                "protected" => array(),
                "private" => array()
            ),
            "properties"=> array(
                "static" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                ),
                "instance" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                )
            ),
            "inheritProperties" => array(
                "static" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                ),
                "instance" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                )
            ),
            "methods" => array(
                "static" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                ),
                "instance" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                )
            ),
            "inheritMethods" => array(
                "static" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                ),
                "instance" => array(
                    "public" => array(),
                    "protected" => array(),
                    "private" => array()
                )
            ),
            "traitProperties" => array(
            ),
            "traitMethods" => array(
            ),
        );
        // consts
        $publicConstArray = &$ret["consts"]["public"];
        $protectedConstArray = &$ret["consts"]["protected"];
        $privateConstArray = &$ret["consts"]["private"];
        foreach ($classReflection->getOwnConstants() as $constant) {
            if ($constant->isPublic()) {
                $publicConstArray[] = $constant;
            } else if ($constant->isProtected()) {
                $protectedConstArray[] = $constant;
            } else if ($constant->isPrivate()) {
                $privateConstArray[] = $constant;
            }
        }
        $publicIneritConstArray = &$ret["inheritConsts"]["public"];
        $protectedIneritConstArray = &$ret["inheritConsts"]["protected"];
        foreach ($classReflection->getInheritedConstants() as $name => $constant) {
            if ($constant->isPublic()) {
                $publicIneritConstArray[] = $constant;
            } else if ($constant->isProtected()) {
                $protectedIneritConstArray[] = $constant;
            }
        }

        $staticPublicProperties = &$ret["properties"]["static"]["public"];
        $staticProtectedProperties = &$ret["properties"]["static"]["protected"];
        $staticPrivateProperties = &$ret["properties"]["static"]["private"];
        $instancePublicProperties = &$ret["properties"]["instance"]["public"];
        $instanceProtectedProperties = &$ret["properties"]["instance"]["protected"];
        $instancePrivateProperties = &$ret["properties"]["instance"]["private"];

        foreach ($classReflection->getOwnProperties() as $property) {
            if ($property->isStatic()) {
                if ($property->isPublic()) {
                    $staticPublicProperties[] = $property;
                } else if ($property->isProtected()) {
                    $staticProtectedProperties[] = $property;
                } else if ($property->isPrivate()) {
                    $staticPrivateProperties[] = $property;
                }
            } else {
                if ($property->isPublic()) {
                    $instancePublicProperties[] = $property;
                } else if ($property->isProtected()) {
                    $instanceProtectedProperties[] = $property;
                } else if ($property->isPrivate()) {
                    $instancePrivateProperties[] = $property;
                }
            }
        }

        $staticInheritPublicProperties = &$ret["inheritProperties"]["static"]["public"];
        $staticInheritProtectedProperties = &$ret["inheritProperties"]["static"]["protected"];
        $instanceInheritPublicProperties = &$ret["inheritProperties"]["instance"]["public"];
        $instanceInheritProtectedProperties = &$ret["inheritProperties"]["instance"]["protected"];

        foreach ($classReflection->getInheritedProperties() as $name => $properties) {
            foreach ($properties as $property) {
                if ($property->isStatic()) {
                    if ($property->isPublic()) {
                        $staticInheritPublicProperties[] = $property;
                    } else if ($property->isProtected()) {
                        $staticInheritProtectedProperties[] = $property;
                    }
                } else {
                    if ($property->isPublic()) {
                        $instanceInheritPublicProperties[] = $property;
                    } else if ($property->isProtected()) {
                        $instanceInheritProtectedProperties[] = $property;
                    }
                }
            }
        }

        $staticPublicMethods = &$ret["methods"]["static"]["public"];
        $staticProtectedMethods = &$ret["methods"]["static"]["protected"];
        $staticPrivateMethods = &$ret["methods"]["static"]["private"];
        $instancePublicMethods = &$ret["methods"]["instance"]["public"];
        $instanceProtectedMethods = &$ret["methods"]["instance"]["protected"];
        $instancePrivateMethods = &$ret["methods"]["instance"]["private"];

        foreach ($classReflection->getOwnMethods() as $method) {
            if ($method->isStatic()) {
                if ($method->isPublic()) {
                    $staticPublicMethods[] = $method;
                } else if ($method->isProtected()) {
                    $staticProtectedMethods[] = $method;
                } else if ($method->isPrivate()) {
                    $staticPrivateMethods[] = $method;
                }
            } else {
                if ($method->isPublic()) {
                    $instancePublicMethods[] = $method;
                } else if ($method->isProtected()) {
                    $instanceProtectedMethods[] = $method;
                } else if ($method->isPrivate()) {
                    $instancePrivateMethods[] = $method;
                }
            }
        }

        $staticInheritPublicMethods = &$ret["inheritMethods"]["static"]["public"];
        $staticInheritProtectedMethods = &$ret["inheritMethods"]["static"]["protected"];
        $instanceInheritPublicMethods = &$ret["inheritMethods"]["instance"]["public"];
        $instanceInheritProtectedMethods = &$ret["inheritMethods"]["instance"]["protected"];

        foreach ($classReflection->getInheritedMethods() as $name => $methods) {
            foreach ($methods as $method) {
                if ($method->isStatic()) {
                    if ($method->isPublic()) {
                        $staticInheritPublicMethods[] = $method;
                    } else if ($method->isProtected()) {
                        $staticInheritProtectedMethods[] = $method;
                    }
                } else {
                    if ($method->isPublic()) {
                        $instanceInheritPublicMethods[] = $method;
                    } else if ($method->isProtected()) {
                        $instanceInheritProtectedMethods[] = $method;
                    }
                }
            }
        }

        $traitProperties = &$ret["traitProperties"];
        // traits about
        foreach ($classReflection->getTraits() as $trait) {
            $traitName = $trait->getName();
            if (!isset($traitProperties[$traitName])) {
                $traitProperties[$traitName] = array();
            }
            foreach ($trait->getOwnProperties() as $property) {
                $traitProperties[$traitName][] = $property;
            }
            if (empty($traitProperties[$traitName])) {
                unset($traitProperties[$traitName]);
            }
        }

        $traitMethods = &$ret["traitMethods"];

        foreach ($classReflection->getTraits() as $trait) {
            $traitName = $trait->getName();
            if (!isset($traitProperties[$traitName])) {
                $traitMethods[$traitName] = array();
            }
            foreach ($trait->getOwnMethods() as $method) {
                $traitMethods[$traitName][] = $method;
            }
        }
        return $ret;
    }

    private function generateSourceCodeForClass(ClassReflectionInterface $classReflection): void
    {
        $content = file_get_contents($classReflection->getFileName());
        $highlightedContent = $this->sourceCodeHighlighter->highlightAndAddLineNumbers($content);

        $this->templateRenderer->renderToFile(
            $this->configuration->getTemplateByName('source'),
            $this->configuration->getDestinationWithPrefixName('source-class-', $classReflection->getName()),
            [
                'apiCatalogKey' => 'classes',
                'activePage' => 'class',
                'activeClass' => $classReflection,
                'fileName' => $classReflection->getFileName(),
                'source' => $highlightedContent,
                'siteCategory' => $this->configuration->getOption('sitecategory'),
                'apiCatalog'=> $this->configuration->getOption('apicatalog')
            ]
        );
    }
}
