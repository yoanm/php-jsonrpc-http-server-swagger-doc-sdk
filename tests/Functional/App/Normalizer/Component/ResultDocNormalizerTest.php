<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResultDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNS;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResultDocNormalizer
 *
 * @group ResultDocNormalizer
 */
class ResultDocNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /** @var DefinitionRefResolver|ObjectProphecy */
    private $definitionRefResolver;
    /** @var ResultDocNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->definitionRefResolver = $this->prophesize(DefinitionRefResolver::class);

        $this->normalizer = new ResultDocNormalizer(
            $this->definitionRefResolver->reveal()
        );
    }

    public function testShouldUseMethodResultDefinitionRefIfMethodResultExist()
    {
        $definitionId = 'definition-id';
        $expectedRef = 'expected-ref';

        /** @var MethodDoc|ObjectProphecy $method */
        $method = $this->prophesize(MethodDoc::class);
        /** @var TypeDocNS\TypeDoc|ObjectProphecy $resultDoc */
        $resultDoc = $this->prophesize(TypeDocNS\TypeDoc::class);
        $method->getResultDoc()
            ->willReturn($resultDoc->reveal())->shouldBeCalled()
        ;

        $this->definitionRefResolver->getMethodDefinitionId(
            $method->reveal(),
            DefinitionRefResolver::METHOD_RESULT_DEFINITION_TYPE
        )
            ->willReturn($definitionId)->shouldBeCalled()
        ;
        $this->definitionRefResolver->getDefinitionRef($definitionId)
            ->willReturn($expectedRef)->shouldBeCalled()
        ;

        $this->assertSame(
            ['$ref' => $expectedRef],
            $this->normalizer->normalize($method->reveal())
        );
    }

    public function testShouldFallbackToDefaultResultDocIfNotResultSpecified()
    {
        /** @var MethodDoc|ObjectProphecy $method */
        $method = $this->prophesize(MethodDoc::class);


        $this->assertSame(
            ['description' => 'Method result'],
            $this->normalizer->normalize($method->reveal())
        );
    }
}
