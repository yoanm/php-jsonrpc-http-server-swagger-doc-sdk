<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Helper\ExternalSchemaNormalizerHelper;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\ServerDoc;

/**
 * Class ExternalSchemaListDocNormalizer
 */
class ExternalSchemaListDocNormalizer
{
    /** @var DefinitionRefResolver */
    private $definitionRefResolver;
    /** @var TypeDocNormalizer */
    private $typeDocNormalizer;
    /** @var ErrorDocNormalizer */
    private $errorDocNormalizer;
    /** @var ShapeNormalizer */
    private $shapeNormalizer;

    /**
     * @param ExternalSchemaNormalizerHelper $externalSchemaNormalizerHelper
     * @param ShapeNormalizer                $shapeNormalizer
     */
    public function __construct(
        DefinitionRefResolver $definitionRefResolver,
        TypeDocNormalizer $typeDocNormalizer,
        ErrorDocNormalizer $errorDocNormalizer,
        ShapeNormalizer $shapeNormalizer
    ) {
        $this->definitionRefResolver = $definitionRefResolver;
        $this->typeDocNormalizer = $typeDocNormalizer;
        $this->errorDocNormalizer = $errorDocNormalizer;
        $this->shapeNormalizer = $shapeNormalizer;
    }

    /**
     * @param ServerDoc $doc
     * @return array
     */
    public function normalize(ServerDoc $doc)
    {
        return array_merge(
            $this->getMethodsExternalSchemaList($doc),
            $this->getMethodErrorsExternalSchemaList($doc),
            $this->getServerErrorsExtraSchemaList($doc),
            $this->getDefaultSchemaList($doc)
        );
    }

    /**
     * @param ServerDoc $doc
     *
     * @return array
     */
    protected function getMethodsExternalSchemaList(ServerDoc $doc)
    {
        $list = [];
        foreach ($doc->getMethodList() as $method) {
            // Merge extra definitions
            $list = array_merge($list, $this->getMethodExternalSchemaList($method));
        }

        return $list;
    }

    /**
     * @param ServerDoc $doc
     *
     * @return array
     */
    protected function getMethodErrorsExternalSchemaList(ServerDoc $doc)
    {
        $list = [];
        foreach ($doc->getMethodList() as $method) {
            $list = array_merge(
                $list,
                $this->normalizeErrorList(
                    $method->getCustomErrorList(),
                    DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
                )
            );
        }

        return $list;
    }


    /**
     * @param ServerDoc $doc
     *
     * @return array
     */
    protected function getServerErrorsExtraSchemaList(ServerDoc $doc)
    {
        return array_merge(
            $this->normalizeErrorList(
                $doc->getGlobalErrorList(),
                DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
            ),
            $this->normalizeErrorList(
                $doc->getServerErrorList(),
                DefinitionRefResolver::SERVER_ERROR_DEFINITION_TYPE
            )
        );
    }

    /**
     * @param MethodDoc $method
     *
     * @return array[]
     */
    protected function getMethodExternalSchemaList(MethodDoc $method) : array
    {
        $list = [];
        // Create request params schema if provided
        if (null !== $method->getParamsDoc()) {
            $key = $this->definitionRefResolver->getMethodDefinitionId(
                $method, DefinitionRefResolver::METHOD_PARAMS_DEFINITION_TYPE
            );
            $list[$key] = $this->typeDocNormalizer->normalize($method->getParamsDoc());
        }

        // Create custom result schema if provided
        if (null !== $method->getResultDoc()) {
            $key = $this->definitionRefResolver->getMethodDefinitionId(
                $method, DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
            );
            $list[$key] = $this->typeDocNormalizer->normalize($method->getResultDoc());
        }

        return $list;
    }

    /**
     * @param ServerDoc $doc
     * @return array
     */
    protected function getDefaultSchemaList(ServerDoc $doc)
    {
        $propertyList = [
            'code' => [
                'type' => 'integer',
            ],
        ];

        $codeList = [];
        foreach ($doc->getServerErrorList() as $errorDoc) {
            $codeList[] = $errorDoc->getCode();
        }
        foreach ($doc->getGlobalErrorList() as $errorDoc) {
            $codeList[] = $errorDoc->getCode();
        }
        foreach ($doc->getMethodList() as $method) {
            foreach ($method->getCustomErrorList() as $errorDoc) {
                $codeList[] = $errorDoc->getCode();
            }
        }

        $codeList = array_unique($codeList);
        if (count($codeList) > 0) {
            $propertyList['code']['enum'] = $codeList;
        }

        return [
            'Default-Error' => [
                'allOf' => [
                    $this->shapeNormalizer->getErrorShapeDefinition(),
                    [
                        'type' => 'object',
                        'properties' => $propertyList,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array  $errorDocList
     * @param string $definitionType
     *
     * @return array
     */
    private function normalizeErrorList(array $errorDocList, $definitionType)
    {
        $list = [];
        foreach ($errorDocList as $errorDoc) {
            $key = $this->definitionRefResolver->getErrorDefinitionId($errorDoc, $definitionType);

            $list[$key] = $this->errorDocNormalizer->normalize($errorDoc);
        }

        return $list;
    }
}