<?php
namespace Tests\Functional\App\Normalizer\Helper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ErrorDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\TypeDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Helper\ExternalSchemaNormalizerHelper;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNS;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Helper\ExternalSchemaNormalizerHelper
 *
 * @group ExternalSchemaNormalizerHelper
 */
class ExternalSchemaNormalizerHelperTest extends TestCase
{
    /** @var TypeDocNormalizer|ObjectProphecy */
    private $typeDocNormalizer;
    /** @var DefinitionRefResolver|ObjectProphecy */
    private $definitionRefResolver;
    /** @var ErrorDocNormalizer|ObjectProphecy */
    private $errorDocNormalizer;
    /** @var ExternalSchemaNormalizerHelper */
    private $normalizer;

    public function setUp()
    {
        $this->typeDocNormalizer = $this->prophesize(TypeDocNormalizer::class);
        $this->definitionRefResolver = $this->prophesize(DefinitionRefResolver::class);
        $this->errorDocNormalizer = $this->prophesize(ErrorDocNormalizer::class);

        $this->normalizer = new ExternalSchemaNormalizerHelper(
            $this->definitionRefResolver->reveal(),
            $this->typeDocNormalizer->reveal(),
            $this->errorDocNormalizer->reveal()
        );
    }

    public function testShouldManageSpecificErrorDefinitionAndKey()
    {
        $expectedDoc = ['expected-doc'];
        $expectedKey = 'expected-key';
        $definitionType = 'definition-type';

        $errorDoc = new ErrorDoc('', 0);

        $this->errorDocNormalizer->normalize($errorDoc)
            ->willReturn($expectedDoc)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getErrorDefinitionId($errorDoc, $definitionType)
            ->willReturn($expectedKey)->shouldBeCalled()
        ;

        $this->assertSame(
            [$expectedDoc, $expectedKey],
            $this->normalizer->getNormalizedErrorDocAndKey($errorDoc, $definitionType)
        );
    }

    public function testShouldManageSpecificMethodDefinitionAndKey()
    {
        $expectedDoc = ['expected-doc'];
        $expectedKey = 'expected-key';
        $definitionType = 'definition-type';

        $methodDoc = new MethodDoc('');
        $typeDoc = new TypeDocNS\TypeDoc();

        $this->typeDocNormalizer->normalize($typeDoc)
            ->willReturn($expectedDoc)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getMethodDefinitionId($methodDoc, $definitionType)
            ->willReturn($expectedKey)->shouldBeCalled()
        ;

        $this->assertSame(
            [$expectedDoc, $expectedKey],
            $this->normalizer->getNormalizedMethodDocAndKey($methodDoc, $typeDoc, $definitionType)
        );
    }

    public function testShouldManageSpecificErrorListDefinitionAndKey()
    {
        $expectedDoc = ['expected-doc'];
        $expectedKey = 'expected-key';
        $expectedDoc2 = ['expected-doc2'];
        $expectedKey2 = 'expected-key2';
        $definitionType = 'definition-type';

        $errorDoc = new ErrorDoc('1', 0);
        $errorDoc2 = new ErrorDoc('2', 0);

        $this->errorDocNormalizer->normalize($errorDoc)
            ->willReturn($expectedDoc)->shouldBeCalled()
        ;
        $this->errorDocNormalizer->normalize($errorDoc2)
            ->willReturn($expectedDoc2)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getErrorDefinitionId($errorDoc, $definitionType)
            ->willReturn($expectedKey)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getErrorDefinitionId($errorDoc2, $definitionType)
            ->willReturn($expectedKey2)->shouldBeCalled()
        ;

        $this->assertSame(
            [
                $expectedKey => $expectedDoc,
                $expectedKey2 => $expectedDoc2,
            ],
            $this->normalizer->normalizeErrorList([$errorDoc, $errorDoc2], $definitionType)
        );
    }
}
