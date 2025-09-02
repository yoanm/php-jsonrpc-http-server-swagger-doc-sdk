# JSON-RPC Http server swagger documentation

[![License](https://img.shields.io/github/license/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg)](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk)
[![Code size](https://img.shields.io/github/languages/code-size/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg)](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk)
![Dependabot Status](https://flat.badgen.net/github/dependabot/yoanm/php-jsonrpc-http-server-swagger-doc-sdk)
![Last commit](https://badgen.net/github/last-commit/yoanm/php-jsonrpc-http-server-swagger-doc-sdk)

[![Scrutinizer Build Status](https://img.shields.io/scrutinizer/build/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk.svg?label=Scrutinizer\&logo=scrutinizer)](https://scrutinizer-ci.com/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/build-status/master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/master.svg?logo=scrutinizer)](https://scrutinizer-ci.com/g/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/?branch=master)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/8f39424add044b43a70bdb238e2f48db)](https://www.codacy.com/gh/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/dashboard?utm_source=github.com\&utm_medium=referral\&utm_content=yoanm/php-jsonrpc-http-server-swagger-doc-sdk\&utm_campaign=Badge_Grade)

[![CI](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/actions/workflows/CI.yml/badge.svg?branch=master)](https://github.com/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/actions/workflows/CI.yml)
[![codecov](https://codecov.io/gh/yoanm/php-jsonrpc-http-server-swagger-doc-sdk/branch/master/graph/badge.svg?token=NHdwEBUFK5)](https://codecov.io/gh/yoanm/php-jsonrpc-http-server-swagger-doc-sdk)

[![Latest Stable Version](https://img.shields.io/packagist/v/yoanm/jsonrpc-http-server-swagger-doc-sdk.svg)](https://packagist.org/packages/yoanm/jsonrpc-http-server-swagger-doc-sdk)
[![Packagist PHP version](https://img.shields.io/packagist/php-v/yoanm/jsonrpc-http-server-swagger-doc-sdk.svg)](https://packagist.org/packages/yoanm/jsonrpc-http-server-swagger-doc-sdk)

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
