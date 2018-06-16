<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;

/**
 * Class ResultDocNormalizer
 */
class ResultDocNormalizer
{
    /** @var DefinitionRefResolver */
    private $definitionRefResolver;

    /**
     * @param DefinitionRefResolver $definitionRefResolver
     */
    public function __construct(DefinitionRefResolver $definitionRefResolver)
    {
        $this->definitionRefResolver = $definitionRefResolver;
    }

    /**
     * @param MethodDoc $method
     *
     * @return array
     */
    public function normalize(MethodDoc $method)
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
