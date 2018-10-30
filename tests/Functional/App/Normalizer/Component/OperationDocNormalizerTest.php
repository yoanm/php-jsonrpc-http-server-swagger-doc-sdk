<?php
namespace Tests\Functional\App\Normalizer\Component;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\RequestDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\MethodDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type as TypeDocNs;

/**
 * @covers \Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer
 *
 * @group OperationDocNormalizer
 */
class OperationDocNormalizerTest extends TestCase
{
    /** @var RequestDocNormalizer|ObjectProphecy */
    private $requestDocTransformer;
    /** @var ResponseDocNormalizer|ObjectProphecy */
    private $responseDocNormalizer;
    /** @var OperationDocNormalizer */
    private $normalizer;

    const DEFAULT_REQUEST_DEFINITION = ['default-request-definition'];
    const DEFAULT_RESPONSE_DEFINITION = ['default-response-definition'];

    public function setUp()
    {
        $this->requestDocTransformer = $this->prophesize(RequestDocNormalizer::class);
        $this->responseDocNormalizer = $this->prophesize(ResponseDocNormalizer::class);

        $this->normalizer = new OperationDocNormalizer(
            new DefinitionRefResolver(),
            $this->requestDocTransformer->reveal(),
            $this->responseDocNormalizer->reveal()
        );
    }

    /**
     * @dataProvider provideManagedMethodDocList
     *
     * @param MethodDoc $methodDoc
     * @param array    $expected
     */
    public function testShouldHandle(MethodDoc $methodDoc, $expected)
    {
        $this->requestDocTransformer->normalize($methodDoc)
            ->willReturn(self::DEFAULT_REQUEST_DEFINITION)->shouldBeCalled()
        ;
        $this->responseDocNormalizer->normalize($methodDoc)
            ->willReturn(self::DEFAULT_RESPONSE_DEFINITION)->shouldBeCalled()
        ;



        $this->assertSame($expected, $this->normalizer->normalize($methodDoc));
    }

    /**
     * @return array
     */
    public function provideManagedMethodDocList()
    {
        $indentString = <<<STRING

    -
STRING;

        return [
            'Simple Operation' => [
                'methodDoc' => new MethodDoc('my-method-name', 'MethodId'),
                'expected' => [
                        'summary' => '"my-method-name" json-rpc method',
                        'operationId' => 'MethodId',
                        'consumes' => ['application/json'],
                        'produces' => ['application/json'],
                        'parameters' => [
                            [
                                'in' => 'body',
                                'name' => 'JsonRpc request',
                                'required' => true,
                                'schema' => self::DEFAULT_REQUEST_DEFINITION
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'JsonRpc response',
                                'schema' => self::DEFAULT_RESPONSE_DEFINITION
                            ]
                        ]
                    ],
            ],
            'Operation with tags' => [
                'methodDoc' => (new MethodDoc('my-method-name', 'MethodId'))
                    ->addTag('tag1')
                    ->addTag('tag2'),
                'expected' => [
                    'summary' => '"my-method-name" json-rpc method',
                    'tags' => ['tag1', 'tag2'],
                    'operationId' => 'MethodId',
                    'consumes' => ['application/json'],
                    'produces' => ['application/json'],
                    'parameters' => [
                        [
                            'in' => 'body',
                            'name' => 'JsonRpc request',
                            'required' => true,
                            'schema' => self::DEFAULT_REQUEST_DEFINITION
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'JsonRpc response',
                            'schema' => self::DEFAULT_RESPONSE_DEFINITION
                        ]
                    ]
                ],
            ],
            'Operation with description' => [
                'methodDoc' => (new MethodDoc('my-method-name', 'MethodId'))
                    ->setDescription('method-description'),
                'expected' => [
                    'summary' => '"my-method-name" json-rpc method',
                    'description' => 'method-description',
                    'operationId' => 'MethodId',
                    'consumes' => ['application/json'],
                    'produces' => ['application/json'],
                    'parameters' => [
                        [
                            'in' => 'body',
                            'name' => 'JsonRpc request',
                            'required' => true,
                            'schema' => self::DEFAULT_REQUEST_DEFINITION
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'JsonRpc response',
                            'schema' => self::DEFAULT_RESPONSE_DEFINITION
                        ]
                    ]
                ],
            ],
            'Operation with custom errors' => [
                'methodDoc' => (new MethodDoc('my-method-name', 'MethodId'))
                    ->addCustomError(new ErrorDoc('Custom1', 1))
                    ->addCustomError(new ErrorDoc('Custom2', 2)),
                'expected' => [
                    'summary' => '"my-method-name" json-rpc method',
                    'description' => 'Could throw custom errors : '
                        .$indentString.'*Custom1* (**Definitions->Error-Custom11**)'
                        .$indentString.'*Custom2* (**Definitions->Error-Custom22**)',
                    'operationId' => 'MethodId',
                    'consumes' => ['application/json'],
                    'produces' => ['application/json'],
                    'parameters' => [
                        [
                            'in' => 'body',
                            'name' => 'JsonRpc request',
                            'required' => true,
                            'schema' => self::DEFAULT_REQUEST_DEFINITION
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'JsonRpc response',
                            'schema' => self::DEFAULT_RESPONSE_DEFINITION
                        ]
                    ]
                ],
            ],
            'Fully configured operation' => [
                'methodDoc' => (new MethodDoc('my-method-name', 'MethodId'))
                    ->addTag('tag1')
                    ->addTag('tag2')
                    ->setDescription('method-description')
                    ->addCustomError(new ErrorDoc('Custom1', 1))
                    ->addCustomError(new ErrorDoc('Custom2', 2)),
                'expected' => [
                    'summary' => '"my-method-name" json-rpc method',
                    'description' => 'method-description'."\n"
                        .'Could throw custom errors : '
                        .$indentString.'*Custom1* (**Definitions->Error-Custom11**)'
                        .$indentString.'*Custom2* (**Definitions->Error-Custom22**)',
                    'tags' => ['tag1', 'tag2'],
                    'operationId' => 'MethodId',
                    'consumes' => ['application/json'],
                    'produces' => ['application/json'],
                    'parameters' => [
                        [
                            'in' => 'body',
                            'name' => 'JsonRpc request',
                            'required' => true,
                            'schema' => self::DEFAULT_REQUEST_DEFINITION
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'JsonRpc response',
                            'schema' => self::DEFAULT_RESPONSE_DEFINITION
                        ]
                    ]
                ],
            ],
        ];
    }
}
