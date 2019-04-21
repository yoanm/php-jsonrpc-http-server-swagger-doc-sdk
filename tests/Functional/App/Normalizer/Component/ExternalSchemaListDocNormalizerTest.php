<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ErrorDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ShapeNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\TypeDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\ServerDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNS;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer
 *
 * @group ExternalSchemaListDocNormalizer
 */
class ExternalSchemaListDocNormalizerTest extends TestCase
{
    /** @var TypeDocNormalizer|ObjectProphecy */
    private $typeDocNormalizer;
    /** @var ErrorDocNormalizer|ObjectProphecy */
    private $errorDocNormalizer;
    /** @var ShapeNormalizer|ObjectProphecy */
    private $shapeNormalizer;
    /** @var ExternalSchemaListDocNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->typeDocNormalizer = $this->prophesize(TypeDocNormalizer::class);
        $this->errorDocNormalizer = $this->prophesize(ErrorDocNormalizer::class);
        $this->shapeNormalizer = $this->prophesize(ShapeNormalizer::class);

        $this->normalizer = new ExternalSchemaListDocNormalizer(
            new DefinitionRefResolver(),
            $this->typeDocNormalizer->reveal(),
            $this->errorDocNormalizer->reveal(),
            $this->shapeNormalizer->reveal()
        );
    }

    public function testShouldAppendDefaultErrorSchema()
    {
        $errorShapeDefinition = ['error-shape-definition'];
        $expectedDefaultErrorSchema = [
            'allOf' => [
                $errorShapeDefinition,
                [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ];
        $doc = new ServerDoc();

        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertArrayHasKey('Default-Error', $normalizedDoc);
        $this->assertSame(
            $expectedDefaultErrorSchema,
            $normalizedDoc['Default-Error']
        );
    }



    public function testShouldAppendDefaultErrorSchemaWithAllErrorCodesFound()
    {
        $errorShapeDefinition = ['error-shape-definition'];
        $serverErrorCodeList = [-1, -2];
        $globalErrorCodeList = [-2, -3]; // Append two times "-2" to check if result have no double
        $customMethodErrorCodeList1 = [-4, -5];
        $customMethodErrorCodeList2 = [-6, -7];
        $expectedErrorCodeList = array_values(
            array_unique(
                array_merge(
                    $serverErrorCodeList,
                    $globalErrorCodeList,
                    $customMethodErrorCodeList1,
                    $customMethodErrorCodeList2
                )
            )
        );
        $expectedDefaultErrorSchema = [
            'allOf' => [
                $errorShapeDefinition,
                [
                    'type' => 'object',
                    'properties' => [
                        'code' => [
                            'type' => 'integer',
                            'enum' => $expectedErrorCodeList
                        ],
                    ],
                ],
            ],
        ];
        $serverErrorCodeList = [
            new ErrorDoc('', $serverErrorCodeList[0]),
            new ErrorDoc('', $serverErrorCodeList[1]),
        ];
        $globalErrorCodeList = [
            new ErrorDoc('', $globalErrorCodeList[0]),
            new ErrorDoc('', $globalErrorCodeList[1]),
        ];
        $customMethodErrorCodeList1 = [
            new ErrorDoc('', $customMethodErrorCodeList1[0]),
            new ErrorDoc('', $customMethodErrorCodeList1[1]),
        ];
        $customMethodErrorCodeList2 = [
            new ErrorDoc('', $customMethodErrorCodeList2[0]),
            new ErrorDoc('', $customMethodErrorCodeList2[1]),
        ];
        $doc = new ServerDoc();
        $doc->addServerError($serverErrorCodeList[0])
            ->addServerError($serverErrorCodeList[1])
        ;
        $doc->addGlobalError($globalErrorCodeList[0])
            ->addGlobalError($globalErrorCodeList[1])
        ;
        $doc->addMethod(
            (new MethodDoc('method-a'))
                ->addCustomError($customMethodErrorCodeList1[0])
                ->addCustomError($customMethodErrorCodeList1[1])
        )
            ->addMethod(
                (new MethodDoc('method-b'))
                    ->addCustomError($customMethodErrorCodeList2[0])
                    ->addCustomError($customMethodErrorCodeList2[1])
            )
        ;

        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($serverErrorCodeList[0])
            ->willReturn(['serverErrorCodeList_0'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($serverErrorCodeList[1])
            ->willReturn(['serverErrorCodeList_1'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($globalErrorCodeList[0])
            ->willReturn(['globalErrorCodeList_0'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($globalErrorCodeList[1])
            ->willReturn(['globalErrorCodeList_1'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($customMethodErrorCodeList1[0])
            ->willReturn(['customMethodErrorCodeList_1_0'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($customMethodErrorCodeList1[1])
            ->willReturn(['customMethodErrorCodeList_1_1'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($customMethodErrorCodeList2[0])
            ->willReturn(['customMethodErrorCodeList_2_0'])->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($customMethodErrorCodeList2[1])
            ->willReturn(['customMethodErrorCodeList_2_1'])->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertArrayHasKey('Default-Error', $normalizedDoc);
        $this->assertSame(
            $expectedDefaultErrorSchema,
            $normalizedDoc['Default-Error']
        );
    }

    public function testShouldAppendServerErrorList()
    {
        $normalizedServerError1 = ['normalized-server-error1'];
        $normalizedServerError2 = ['normalized-server-error2'];
        $errorShapeDefinition = ['error-shape-definition'];
        $serverError1 = new ErrorDoc('firstError', 1);
        $serverError2 = new ErrorDoc('secondError', 2);
        $doc = new ServerDoc();
        $doc->addServerError($serverError1)
            ->addServerError($serverError2)
        ;

        $this->errorDocNormalizer->normalize($serverError1)
            ->willReturn($normalizedServerError1)->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($serverError2)
            ->willReturn($normalizedServerError2)->shouldBeCalled()
        ;
        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertSame(
            $normalizedServerError1,
            $normalizedDoc['ServerError-FirstError1']
        );
        $this->assertSame(
            $normalizedServerError2,
            $normalizedDoc['ServerError-SecondError2']
        );
    }

    public function testShouldAppendGlobalErrorList()
    {
        $normalizedGlobalError1 = ['normalized-global-error1'];
        $normalizedGlobalError2 = ['normalized-global-error2'];
        $errorShapeDefinition = ['error-shape-definition'];
        $globalError1 = new ErrorDoc('firstError', 1);
        $globalError2 = new ErrorDoc('secondError', 2);
        $doc = new ServerDoc();
        $doc->addGlobalError($globalError1)
            ->addGlobalError($globalError2)
        ;

        $this->errorDocNormalizer->normalize($globalError1)
            ->willReturn($normalizedGlobalError1)->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($globalError2)
            ->willReturn($normalizedGlobalError2)->shouldBeCalled()
        ;
        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertSame(
            $normalizedGlobalError1,
            $normalizedDoc['Error-FirstError1']
        );
        $this->assertSame(
            $normalizedGlobalError2,
            $normalizedDoc['Error-SecondError2']
        );
    }

    public function testShouldAppendCustomMethodErrorList()
    {
        $normalizedCustomMethodError1 = ['normalized-customMethod-error1'];
        $normalizedCustomMethodError2 = ['normalized-customMethod-error2'];
        $errorShapeDefinition = ['error-shape-definition'];
        $customMethodError1 = new ErrorDoc('firstCustomError', 1);
        $customMethodError2 = new ErrorDoc('secondCustomError', 2);
        $doc = new ServerDoc();
        $doc->addMethod((new MethodDoc('method-name'))->addCustomError($customMethodError1))
            ->addMethod((new MethodDoc('method-name-2'))->addCustomError($customMethodError2))
        ;

        $this->errorDocNormalizer->normalize($customMethodError1)
            ->willReturn($normalizedCustomMethodError1)->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($customMethodError2)
            ->willReturn($normalizedCustomMethodError2)->shouldBeCalled()
        ;
        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertSame(
            $normalizedCustomMethodError1,
            $normalizedDoc['Error-FirstCustomError1']
        );
        $this->assertSame(
            $normalizedCustomMethodError2,
            $normalizedDoc['Error-SecondCustomError2']
        );
    }

    public function testShouldAppendMethodResultList()
    {
        $normalizedResult1 = ['normalized-result1'];
        $normalizedResult2 = ['normalized-result2'];
        $errorShapeDefinition = ['error-shape-definition'];
        $result1 = new TypeDocNS\StringDoc();
        $result2 = new TypeDocNS\BooleanDoc();
        $doc = new ServerDoc();
        $doc->addMethod((new MethodDoc('method-name'))->setResultDoc($result1))
            ->addMethod((new MethodDoc('method-name-2')))
            ->addMethod((new MethodDoc('method-name-3'))->setResultDoc($result2))
        ;

        $this->typeDocNormalizer->normalize($result1)
            ->willReturn($normalizedResult1)->shouldBeCalled()
        ;
        $this->typeDocNormalizer->normalize($result2)
            ->willReturn($normalizedResult2)->shouldBeCalled()
        ;
        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertSame(
            $normalizedResult1,
            $normalizedDoc['Method-Method-name-Result']
        );
        $this->assertSame(
            $normalizedResult2,
            $normalizedDoc['Method-Method-name-3-Result']
        );
    }

    public function testShouldAppendMethodParamsList()
    {
        $normalizedParams1 = ['normalized-params1'];
        $normalizedParams2 = ['normalized-params2'];
        $errorShapeDefinition = ['error-shape-definition'];
        $params1 = new TypeDocNS\ObjectDoc();
        $params2 = new TypeDocNS\ArrayDoc();
        $doc = new ServerDoc();
        $doc->addMethod((new MethodDoc('method-name'))->setParamsDoc($params1))
            ->addMethod((new MethodDoc('method-name-2')))
            ->addMethod((new MethodDoc('method-name-3'))->setParamsDoc($params2))
        ;

        $this->typeDocNormalizer->normalize($params1)
            ->willReturn($normalizedParams1)->shouldBeCalled()
        ;
        $this->typeDocNormalizer->normalize($params2)
            ->willReturn($normalizedParams2)->shouldBeCalled()
        ;
        $this->shapeNormalizer->getErrorShapeDefinition()
            ->willReturn($errorShapeDefinition)->shouldBeCalled()
        ;

        $normalizedDoc = $this->normalizer->normalize($doc);

        $this->assertSame(
            $normalizedParams1,
            $normalizedDoc['Method-Method-name-RequestParams']
        );
        $this->assertSame(
            $normalizedParams2,
            $normalizedDoc['Method-Method-name-3-RequestParams']
        );
    }
}
