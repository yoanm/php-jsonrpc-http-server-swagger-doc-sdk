<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ShapeNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNS;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer
 *
 * @group ResponseDocNormalizer
 */
class ResponseDocNormalizerTest extends TestCase
{
    /** @var DefinitionRefResolver|ObjectProphecy */
    private $definitionRefResolver;
    /** @var ShapeNormalizer|ObjectProphecy */
    private $shapeNormalizer;
    /** @var ResponseDocNormalizer */
    private $normalizer;

    public function setUp()
    {
        $this->definitionRefResolver = $this->prophesize(DefinitionRefResolver::class);
        $this->shapeNormalizer = $this->prophesize(ShapeNormalizer::class);

        $this->normalizer = new ResponseDocNormalizer(
            $this->definitionRefResolver->reveal(),
            $this->shapeNormalizer->reveal()
        );
    }

    public function testShouldHandleBasicResponse()
    {
        $responseShape = ['responseShape'];
        $defaultErrorRef = 'default-error-ref';
        $defaultExpectedMethodResult = ['description' => 'Method result'];

        /** @var MethodDoc|ObjectProphecy $method */
        $method = $this->prophesize(MethodDoc::class);

        $this->shapeNormalizer->getResponseShapeDefinition()
            ->willReturn($responseShape)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef('Default-Error')
            ->willReturn($defaultErrorRef)->shouldBeCalled()
        ;

        $this->assertSame(
            [
                'allOf' => [
                    $responseShape,
                    [
                        'type' => 'object',
                        'properties' => ['result' => $defaultExpectedMethodResult],
                    ],
                    [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['$ref' => $defaultErrorRef]
                        ],
                    ],
                ],
            ],
            $this->normalizer->normalize($method->reveal())
        );
    }

    public function testShouldHandleResponseWithMethodResult()
    {
        $responseShape = ['responseShape'];
        $defaultErrorRef = 'default-error-ref';
        $methodResultDefinitionId = 'method-result-definition-id';
        $methodResultDefinitionIdRef = 'method-result-definition-id-ref';

        /** @var MethodDoc|ObjectProphecy $method */
        $method = $this->prophesize(MethodDoc::class);
        /** @var TypeDocNS\TypeDoc|ObjectProphecy $methodResultDoc */
        $methodResultDoc = $this->prophesize(TypeDocNS\TypeDoc::class);

        $this->shapeNormalizer->getResponseShapeDefinition()
            ->willReturn($responseShape)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef('Default-Error')
            ->willReturn($defaultErrorRef)
            ->shouldBeCalled()
        ;

        $method->getResultDoc()
            ->willReturn($methodResultDoc->reveal())->shouldBeCalled()
        ;
        $this->definitionRefResolver->getMethodDefinitionId(
            $method->reveal(),
            DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
        )
            ->willReturn($methodResultDefinitionId)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($methodResultDefinitionId)
            ->willReturn($methodResultDefinitionIdRef)->shouldBeCalled()
        ;

        $this->assertSame(
            [
                'allOf' => [
                    $responseShape,
                    [
                        'type' => 'object',
                        'properties' => [
                            'result' => ['$ref' => $methodResultDefinitionIdRef]
                        ],
                    ],
                    [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['$ref' => $defaultErrorRef]
                        ],
                    ],
                ],
            ],
            $this->normalizer->normalize($method->reveal())
        );
    }
}
