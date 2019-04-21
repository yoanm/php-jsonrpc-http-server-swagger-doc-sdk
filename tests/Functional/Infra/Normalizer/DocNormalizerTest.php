<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\Infra\Normalizer\DocNormalizer;
use Yoanm\JsonRpcServerDoc\Domain\Model\HttpServerDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\TagDoc;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\Infra\Normalizer\DocNormalizer
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

    protected function setUp(): void
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
            'Doc with host' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setHost('my-host')
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'host' => 'my-host',
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with basePath' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setBasePath('/my-basePath')
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'basePath' => '/my-basePath',
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Doc with schemes' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setSchemeList(['my-scheme'])
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'schemes' => ['my-scheme'],
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
            'Doc with methods and endpoint' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setEndpoint('/my-endpoint')
                    ->addMethod(new MethodDoc('method-1', 'MethodId1'))
                    ->addMethod(new MethodDoc('method-2', 'MethodId2'))
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'paths' => [
                        '/MethodId1/../my-endpoint' => ['post' => self::DEFAULT_OPERATION_DOC],
                        '/MethodId2/../my-endpoint' => ['post' => self::DEFAULT_OPERATION_DOC],
                    ],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
            'Fully configured Doc' => [
                'errorDoc' => (new HttpServerDoc())
                    ->setHost('my-host')
                    ->setBasePath('/my-basePath')
                    ->setSchemeList(['my-scheme'])
                    ->setEndpoint('/my-endpoint')
                    ->setName('my-name')
                    ->setVersion('my-version')
                    ->addTag(new TagDoc('tag1'))
                    ->addTag((new TagDoc('tag2'))->setDescription('tag2 desc'))
                    ->addMethod(new MethodDoc('method-1', 'MethodId1'))
                    ->addMethod(new MethodDoc('method-2', 'MethodId2'))
                ,
                'expected' => [
                    'swagger' => '2.0',
                    'info' => [
                        'title' => 'my-name',
                        'version' => 'my-version',
                    ],
                    'host' => 'my-host',
                    'basePath' => '/my-basePath',
                    'schemes' => ['my-scheme'],
                    'tags' => [
                        ['name' => 'tag1'],
                        ['name' => 'tag2', 'description' => 'tag2 desc'],
                    ],
                    'paths' => [
                        '/MethodId1/../my-endpoint' => ['post' => self::DEFAULT_OPERATION_DOC],
                        '/MethodId2/../my-endpoint' => ['post' => self::DEFAULT_OPERATION_DOC],
                    ],
                    'definitions' => self::DEFAULT_EXTERNAL_LIST_DOC,
                ],
            ],
        ];
    }
}
