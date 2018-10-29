<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;

/**
 * Class ResponseDocNormalizer
 */
class ResponseDocNormalizer
{
    /** @var DefinitionRefResolver */
    private $definitionRefResolver;
    /** @var ShapeNormalizer */
    private $shapeNormalizer;

    /**
     * @param DefinitionRefResolver $definitionRefResolver
     * @param ShapeNormalizer       $shapeNormalizer
     */
    public function __construct(
        DefinitionRefResolver $definitionRefResolver,
        ShapeNormalizer $shapeNormalizer
    ) {
        $this->definitionRefResolver = $definitionRefResolver;
        $this->shapeNormalizer = $shapeNormalizer;
    }

    /**
     * @param MethodDoc $method
     *
     * @return array
     */
    public function normalize(MethodDoc $method)
    {
        return [
            'allOf' => array_merge(
                [
                    $this->shapeNormalizer->getResponseShapeDefinition(),
                    [
                        'type' => 'object',
                        'properties' => ['result' => $this->getMethodResultArrayDoc($method)],
                    ],
                ],
                array_map(
                    function ($errorIdentifier) {
                        return [
                            'type' => 'object',
                            'properties' => [
                                'error' => [
                                    '$ref' => $this->definitionRefResolver->getDefinitionRef(
                                        $this->definitionRefResolver->getErrorDefinitionId(
                                            new ErrorDoc('', 0, null, null, $errorIdentifier),
                                            DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
                                        )
                                    )
                                ]
                            ],
                        ];
                    },
                    $method->getGlobalErrorRefList()
                ),
                [
                    [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['$ref' => $this->definitionRefResolver->getDefinitionRef('Default-Error')]
                        ],
                    ]
                ]
            )
        ];
    }

    /**
     * @param MethodDoc $method
     *
     * @return array
     */
    protected function getMethodResultArrayDoc(MethodDoc $method)
    {
        if (null !== $method->getResultDoc()) {
            return [
                '$ref' => $this->definitionRefResolver->getDefinitionRef(
                    $this->definitionRefResolver->getMethodDefinitionId(
                        $method,
                        DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
                    )
                )
            ];
        }

        return ['description' => 'Method result'];
    }
}
