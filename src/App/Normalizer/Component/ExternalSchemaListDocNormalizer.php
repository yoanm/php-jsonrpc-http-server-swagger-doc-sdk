<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\ServerDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\TypeDoc;

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
     * @param DefinitionRefResolver $definitionRefResolver
     * @param TypeDocNormalizer     $typeDocNormalizer
     * @param ErrorDocNormalizer    $errorDocNormalizer
     * @param ShapeNormalizer       $shapeNormalizer
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
        $list = $this->appendAndNormalizeIfNotNull(
            $this->definitionRefResolver->getMethodDefinitionId(
                $method,
                DefinitionRefResolver::METHOD_PARAMS_DEFINITION_TYPE
            ),
            $method->getParamsDoc(),
            $list
        );

        // Create custom result schema if provided
        $list = $this->appendAndNormalizeIfNotNull(
            $this->definitionRefResolver->getMethodDefinitionId(
                $method,
                DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
            ),
            $method->getResultDoc(),
            $list
        );

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

        $errorList = array_merge($doc->getServerErrorList(), $doc->getGlobalErrorList());
        $errorList = array_reduce(
            array_map(
                function (MethodDoc $methodDoc) {
                    return $methodDoc->getCustomErrorList();
                },
                $doc->getMethodList()
            ),
            function (array $carry, array $subErrorList) {
                $carry = array_merge($carry, $subErrorList);

                return $carry;
            },
            $errorList
        );
        $codeList = array_unique(
            array_map(
                function (ErrorDoc $errorDoc) {
                    return $errorDoc->getCode();
                },
                $errorList
            )
        );

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

    /**
     * @param string       $key
     * @param TypeDoc|null $value
     * @param array        $list
     *
     * @return array
     */
    protected function appendAndNormalizeIfNotNull(string $key, $value, array $list = [])
    {
        if (null !== $value) {
            $list[$key] = $this->typeDocNormalizer->normalize($value);
        }

        return $list;
    }
}
