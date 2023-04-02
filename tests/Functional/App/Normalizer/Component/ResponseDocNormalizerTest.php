<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ShapeNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNS;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer
 *
 * @group ResponseDocNormalizer
 */
class ResponseDocNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /** @var DefinitionRefResolver|ObjectProphecy */
    private $definitionRefResolver;
    /** @var ShapeNormalizer|ObjectProphecy */
    private $shapeNormalizer;
    /** @var ResponseDocNormalizer */
    private $normalizer;

    protected function setUp(): void
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

        $method->getGlobalErrorRefList()
            ->willReturn([])
            ->shouldBeCalled()
        ;
        $method->getResultDoc()
            ->willReturn(null)
            ->shouldBeCalled()
        ;

        $this->shapeNormalizer->getResponseShapeDefinition()
            ->willReturn($responseShape)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef('Default-Error')
            ->willReturn($defaultErrorRef)
            ->shouldBeCalled()
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

        $method->getGlobalErrorRefList()
            ->willReturn([])
            ->shouldBeCalled()
        ;
        $method->getResultDoc()
            ->willReturn(null)
            ->shouldBeCalled()
        ;
        $method->getResultDoc()
            ->willReturn($methodResultDoc->reveal())
            ->shouldBeCalled()
        ;

        $this->shapeNormalizer->getResponseShapeDefinition()
            ->willReturn($responseShape)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef('Default-Error')
            ->willReturn($defaultErrorRef)
            ->shouldBeCalled()
        ;

        $this->definitionRefResolver->getMethodDefinitionId(
            $method->reveal(),
            DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
        )
            ->willReturn($methodResultDefinitionId)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($methodResultDefinitionId)
            ->willReturn($methodResultDefinitionIdRef)
            ->shouldBeCalled()
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

    public function testShouldHandleGlobalErrorRef()
    {
        $responseShape = ['responseShape'];
        $defaultErrorRef = 'default-error-ref';
        $methodResultDefinitionId = 'method-result-definition-id';
        $methodResultDefinitionIdRef = 'method-result-definition-id-ref';
        $globalErrorRef1 = 'global-error-ref-1';
        $globalErrorRef2 = 'global-error-ref-2';
        $expectedGlobalErrorRef1 = 'expected-global-error-ref-1';
        $expectedGlobalErrorRef2 = 'expected-global-error-ref-1';
        $expectedRefGlobalErrorRef1 = 'expected-ref-global-error-ref-1';
        $expectedRefGlobalErrorRef2 = 'expected-ref-global-error-ref-1';

        /** @var MethodDoc|ObjectProphecy $method */
        $method = $this->prophesize(MethodDoc::class);
        /** @var TypeDocNS\TypeDoc|ObjectProphecy $methodResultDoc */
        $methodResultDoc = $this->prophesize(TypeDocNS\TypeDoc::class);

        $method->getGlobalErrorRefList()
            ->willReturn([$globalErrorRef1, $globalErrorRef2])
            ->shouldBeCalled()
        ;
        $method->getResultDoc()
            ->willReturn(null)
            ->shouldBeCalled()
        ;
        $method->getResultDoc()
            ->willReturn($methodResultDoc->reveal())
            ->shouldBeCalled()
        ;

        $this->shapeNormalizer->getResponseShapeDefinition()
            ->willReturn($responseShape)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef('Default-Error')
            ->willReturn($defaultErrorRef)
            ->shouldBeCalled()
        ;

        $this->definitionRefResolver->getErrorDefinitionId(
            Argument::allOf(
                Argument::type(ErrorDoc::class),
                Argument::which('getIdentifier', 'Global-error-ref-1')
            ),
            DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
        )
            ->willReturn($expectedGlobalErrorRef1)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getErrorDefinitionId(
            Argument::allOf(
                Argument::type(ErrorDoc::class),
                Argument::which('getIdentifier', 'Global-error-ref-2')
            ),
            DefinitionRefResolver::CUSTOM_ERROR_DEFINITION_TYPE
        )
            ->willReturn($expectedGlobalErrorRef2)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getMethodDefinitionId(
            $method->reveal(),
            DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
        )
            ->willReturn($methodResultDefinitionId)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($methodResultDefinitionId)
            ->willReturn($methodResultDefinitionIdRef)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($expectedGlobalErrorRef1)
            ->willReturn($expectedRefGlobalErrorRef1)
            ->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($expectedGlobalErrorRef2)
            ->willReturn($expectedRefGlobalErrorRef2)
            ->shouldBeCalled()
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
                            'error' => ['$ref' => $expectedRefGlobalErrorRef1]
                        ],
                    ],
                    [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['$ref' => $expectedRefGlobalErrorRef2]
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
