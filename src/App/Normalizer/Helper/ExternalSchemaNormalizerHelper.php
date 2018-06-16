<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Helper;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ErrorDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\TypeDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\TypeDoc;

/**
 * Class ExternalSchemaNormalizerHelper
 */
class ExternalSchemaNormalizerHelper
{
    /** @var TypeDocNormalizer */
    private $typeDocNormalizer;
    /** @var DefinitionRefResolver */
    private $definitionRefResolver;
    /** @var ErrorDocNormalizer */
    private $errorDocNormalizer;

    /**
     * @param DefinitionRefResolver $definitionRefResolver
     * @param typeDocNormalizer     $typeDocNormalizer
     * @param ErrorDocNormalizer    $errorDocNormalizer
     */
    public function __construct(
        DefinitionRefResolver $definitionRefResolver,
        TypeDocNormalizer $typeDocNormalizer,
        ErrorDocNormalizer $errorDocNormalizer
    ) {
        $this->definitionRefResolver = $definitionRefResolver;
        $this->typeDocNormalizer = $typeDocNormalizer;
        $this->errorDocNormalizer = $errorDocNormalizer;
    }

    /**
     * @param ErrorDoc $errorDoc
     * @param string   $definitionType
     *
     * @return array Normalized doc (first value) and external schema key (second value)
     */
    public function getNormalizedErrorDocAndKey(ErrorDoc $errorDoc, $definitionType)
    {
        return [
            $this->errorDocNormalizer->normalize($errorDoc),
            $this->definitionRefResolver->getErrorDefinitionId($errorDoc, $definitionType)
        ];
    }

    /**
     * @param MethodDoc $methodDoc
     * @param TypeDoc   $typeDoc
     * @param string    $definitionType
     *
     * @return array Normalized type doc (first value) and external schema key (second value)
     */
    public function getNormalizedMethodDocAndKey(MethodDoc $methodDoc, TypeDoc $typeDoc, $definitionType)
    {
        return [
            $this->typeDocNormalizer->normalize($typeDoc),
            $this->definitionRefResolver->getMethodDefinitionId($methodDoc, $definitionType)
        ];
    }

    /**
     * @param array  $errorDocList
     * @param string $definitionType
     *
     * @return array
     */
    public function normalizeErrorList(array $errorDocList, $definitionType)
    {
        $list = [];
        foreach ($errorDocList as $errorDoc) {
            list ($doc, $key) = $this->getNormalizedErrorDocAndKey($errorDoc, $definitionType);

            $list[$key] = $doc;
        }

        return $list;
    }
}
