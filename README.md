# JSON-RPC Http server documentation SDK
[![License](https://img.shields.io/github/license/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg)](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk) [![Code size](https://img.shields.io/github/languages/code-size/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg)](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk) [![Dependencies](https://img.shields.io/librariesio/github/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg)](https://libraries.io/packagist/yoanm%2Fjsonrpc-http-server-swagger-doc-sdk)

[![Scrutinizer Build Status](https://img.shields.io/scrutinizer/build/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg?label=Scrutinizer&logo=scrutinizer)](https://scrutinizer-ci.com/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/build-status/master) [![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/master.svg?logo=scrutinizer)](https://scrutinizer-ci.com/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/?branch=master) [![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/master.svg?logo=scrutinizer)](https://scrutinizer-ci.com/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/?branch=master)

[![Travis Build Status](https://img.shields.io/travis/com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/master.svg?label=Travis&logo=travis)](https://travis-ci.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk) [![Travis PHP versions](https://img.shields.io/travis/php-v/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg?logo=travis)](https://php.net/)

[![Latest Stable Version](https://img.shields.io/packagist/v/yoanm/jsonrpc-http-server-swagger-doc-sdk.svg)](https://packagist.org/packages/yoanm/jsonrpc-http-server-swagger-doc-sdk) [![Packagist PHP version](https://img.shields.io/packagist/php-v/yoanm/jsonrpc-http-server-swagger-doc-sdk.svg)](https://packagist.org/packages/yoanm/jsonrpc-http-server-swagger-doc-sdk)

SDK to generate Http JSON-RPC server documentation for Swagger v2.0

See [`yoanm/symfony-jsonrpc-http-server-swagger-doc`](https://github.com/yoanm/symfony-jsonrpc-http-server-swagger-doc) for automatic dependency injection.

## How to use

Create the normalizer : 
```php
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ErrorDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\RequestDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ResponseDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\SchemaTypeNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ShapeNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\TypeDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Resolver\DefinitionRefResolver;
use Yoanm\JsonRpcHttpServerSwaggerDoc\Infra\Normalizer\DocNormalizer;

$shapeNormalizer = new ShapeNormalizer();
$definitionRefResolver = new DefinitionRefResolver();
$typeDocNormalizer = new TypeDocNormalizer(
    new SchemaTypeNormalizer()
);

$normalizer = new DocNormalizer(
    new ExternalSchemaListDocNormalizer(
        $definitionRefResolver,
        $typeDocNormalizer,
        new ErrorDocNormalizer(
            $typeDocNormalizer,
            $shapeNormalizer
        ),
        $shapeNormalizer
    ),
    new OperationDocNormalizer(
        $definitionRefResolver,
        new RequestDocNormalizer(
            $definitionRefResolver,
            $shapeNormalizer
        ),
        new ResponseDocNormalizer(
            $definitionRefResolver,
            $shapeNormalizer
        )
    )
);
```

Then you can convert `ServerDoc` or `HttpServerDoc` by doing : 
```php
use Yoanm\JsonRpcServerDoc\Domain\Model\ServerDoc;

$serverDoc = new ServerDoc();
// Configure server doc
...
// Add methods documentation
...
// Then normalize
/** @var array $swaggerDoc */
$swaggerDoc = $normalizer->normalize($serverDoc);
```

## Contributing
See [contributing note](./CONTRIBUTING.md)
