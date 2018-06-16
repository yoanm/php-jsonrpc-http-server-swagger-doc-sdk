<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;

/**
 * Class OperationDocNormalizer
 */
class OperationDocNormalizer
{
    /** @var RequestDocNormalizer */
    private $requestDocTransformer;
    /** @var ResponseDocNormalizer */
    private $responseDocNormalizer;
    /** @var DefinitionRefResolver */
    private $definitionRefResolver;

    /**
     * @param DefinitionRefResolver $definitionRefResolver
     * @param RequestDocNormalizer  $requestDocTransformer
     * @param ResponseDocNormalizer $responseDocNormalizer
     */
    public function __construct(
        DefinitionRefResolver $definitionRefResolver,
        RequestDocNormalizer $requestDocTransformer,
        ResponseDocNormalizer $responseDocNormalizer
    ) {
        $this->requestDocTransformer = $requestDocTransformer;
        $this->responseDocNormalizer = $responseDocNormalizer;
        $this->definitionRefResolver = $definitionRefResolver;
    }

    /**
     * @param MethodDoc $method
     *
     * @return array
     */
    public function normalize(MethodDoc $method) : array
    {
        $docTags = [];

        if (count($method->getTagList())) {
            $docTags['tags'] = $method->getTagList();
        }

        return [
                'summary' => sprintf('"%s" json-rpc method', $method->getMethodName()),
            ]
            + $this->getDescriptionDoc($method)
            + $docTags
            + [
                'operationId' => $method->getIdentifier(),
                'consumes' => ['application/json'],
                'produces' => ['application/json'],
                'parameters' => [
                    [
                        'in' => 'body',
                        'name' => 'JsonRpc request',
                        'required' => true,
                        'schema' => $this->requestDocTransformer->normalize($method)
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'JsonRpc response',
                        'schema' => $this->responseDocNormalizer->normalize($method),
                    ]
                ]
            ]
        ;
    }

    /**
     * @param MethodDoc $method
     * @return array
     */
    protected function getDescriptionDoc(MethodDoc $method)
    {
        $docDescription = [];
        if (null !== $method->getDescription()) {
            $docDescription['description'] = $method->getDescription();
        }

        $responseDescription = $this->getResponseDescription($method);
        if (null !== $responseDescription) {
            $docDescription['description'] = isset($docDescription['description'])
                && strlen($docDescription['description']) > 0
                ? $docDescription['description'] . "\n"
                : ''
            ;
            $docDescription['description'] = $docDescription['description'] . $responseDescription;
        }

        return $docDescription;
    }

    /**
     * @param MethodDoc $method
     *
     * @return string|null
     */
    private function getResponseDescription(MethodDoc $method)
    {
        if (count($method->getCustomErrorList())) {
            $self = $this;
            // Better to use raw string instead of \t
            $indentString = <<<STRING

    -
STRING;

            return sprintf(
                "Could throw custom errors : %s%s",
                $indentString,
                implode(
                    $indentString,
                    array_map(
                        function (ErrorDoc $errorDoc) use ($self) {
                            return sprintf(
                                '*%s* (**Models->%s**)',
                                $errorDoc->getTitle(),
                                $self->definitionRefResolver->getErrorDefinitionId(
                                    $errorDoc,
                                    DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
                                )
                            );
                        },
                        $method->getCustomErrorList()
                    )
                )
            );
        }

        return null;
    }
}
