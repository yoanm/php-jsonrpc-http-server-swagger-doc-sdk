<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\Infra\Normalizer\DocNormalizer;
use Yoanm\JsonRpcServerDoc\Domain\Model\HttpServerDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\TagDoc;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\DocNormalizer
 *
 * @group DocNormalizer
 */
class DocNormalizerTest extends TestCase
{
    /** @var ExternalSchemaListDocNormalizer|ObjectProphecy */
    private $externalSchemaListDocNormalizer;
    /** @var OperationDocNormalizer|ObjectProphecy */
    private $operationDocNormalizer;
    /** @var DocNormalizer */
    private $normalizer;

    const DEFAULT_OPERATION_DOC = ['default-opertation-doc'];
    const DEFAULT_EXTERNAL_LIST_DOC = ['default-external-list-doc'];

    public function setUp()
    {
        $this->externalSchemaListDocNormalizer = $this->prophesize(ExternalSchemaListDocNormalizer::class);
        $this->operationDocNormalizer = $this->prophesize(OperationDocNormalizer::class);

        $this->normalizer = new DocNormalizer(
            $this->externalSchemaListDocNormalizer->reveal(),
            $this->operationDocNormalizer->reveal()
        );
    }

    /**
     * @dataProvider provideManagedErrorDocList
     *
     * @param HttpServerDoc $serverDoc
     * @param array         $expected
     */
    public function testShouldHandle(HttpServerDoc $serverDoc, $expected)
    {
        $this->externalSchemaListDocNormalizer->normalize($serverDoc)
            ->willReturn(self::DEFAULT_EXTERNAL_LIST_DOC)->shouldBeCalled()
        ;

        foreach ($serverDoc->getMethodList() as $method) {
            $this->operationDocNormalizer->normalize($method)
                ->willReturn(self::DEFAULT_OPERATION_DOC)->shouldBeCalled()
            ;
        }

        $this->assertSame($expected, $this->normalizer->normalize($serverDoc));
    }

    /**
     * @return array
     */
    public function provideManagedErrorDocList()
    {
        return [
            'Simple Doc' => [
                'errorDoc' => new HttpServerDoc(),
                'expected' => [
                    'swagger' => '2.0',
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with name' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setName('my-name')
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'info' => ['title' => 'my-name'],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with version' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setVersion('my-version')
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'info' => ['version' => 'my-version'],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with tags' => [
                'errorDoc' => (new HttpServerDoc())
                    ->addTag(new TagDoc('tag1'))
                    ->addTag((new TagDoc('tag2'))->setDescription('tag2 desc'))
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'tags' => [
                        ['name' => 'tag1'],
                        ['name' => 'tag2', 'description' => 'tag2 desc'],
                    ],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with methods' => [
                'errorDoc' => (new HttpServerDoc())
                    ->addMethod(new MethodDoc('method-1', 'MethodId1'))
                    ->addMethod(new MethodDoc('method-2', 'MethodId2'))
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'paths' => [
                        '/MethodId1/..' => ['post' => self::DEFAULT_OPERATION_DOC],
                        '/MethodId2/..' => ['post' => self::DEFAULT_OPERATION_DOC],
                    ],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
        ];
    }
}
