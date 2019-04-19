<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\Infra\Normalizer;

use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Helper\ArrayAppendHelperTrait;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\ExternalSchemaListDocNormalizer;
use Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component\OperationDocNormalizer;
use Yoanm\JsonRpcServerDoc\Domain\Model\HttpServerDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\TagDoc;

/**
 * Class DocNormalizer
 */
class DocNormalizer
{
    use ArrayAppendHelperTrait;

    /** @var ExternalSchemaListDocNormalizer */
    private $externalSchemaListDocNormalizer;
    /** @var OperationDocNormalizer */
    private $operationDocNormalizer;

    /**
     * @param ExternalSchemaListDocNormalizer $externalSchemaListDocNormalizer
     * @param OperationDocNormalizer          $operationDocNormalizer
     */
    public function __construct(
        ExternalSchemaListDocNormalizer $externalSchemaListDocNormalizer,
        OperationDocNormalizer $operationDocNormalizer
    ) {
        $this->externalSchemaListDocNormalizer = $externalSchemaListDocNormalizer;
        $this->operationDocNormalizer = $operationDocNormalizer;
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function normalize(HttpServerDoc $doc) : array
    {
        return [
                'swagger' => '2.0',
            ]
            + $this->infoArray($doc)
            + $this->serverArray($doc)
            + $this->tagsArray($doc)
            + $this->pathsArray($doc)
            + $this->externalSchemaListArray($doc)
        ;
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     */
    protected function infoArray(HttpServerDoc $doc) : array
    {
        $infoArray = [];
        $infoArray = $this->appendIfValueNotNull('title', $doc->getName(), $infoArray);
        $infoArray = $this->appendIfValueNotNull('version', $doc->getVersion(), $infoArray);

        return $this->appendIfValueHaveSiblings('info', $infoArray);
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     */
    protected function serverArray(HttpServerDoc $doc) : array
    {
        $docArray = [];
        $docArray = $this->appendIfValueNotNull('host', $doc->getHost(), $docArray);
        $docArray = $this->appendIfValueNotNull('basePath', $doc->getBasePath(), $docArray);
        $docArray = $this->appendIfValueHaveSiblings('schemes', $doc->getSchemeList(), $docArray);

        return $docArray;
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     */
    protected function tagsArray(HttpServerDoc $doc) : array
    {
        $self = $this;

        return $this->appendIfValueHaveSiblings(
            'tags',
            array_map(
                function (TagDoc $tagDoc) use ($self) {
                    return $self->convertToTagDoc($tagDoc);
                },
                $doc->getTagList()
            )
        );
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     */
    protected function pathsArray(HttpServerDoc $doc) : array
    {
        $paths = [];
        foreach ($doc->getMethodList() as $method) {
            $operationDoc = $this->operationDocNormalizer->normalize($method);

            // As JSON-RPC use only one endpoint
            // and openApi does not handle multiple request definition for the same endpoint
            // => create a fake (but usable) endpoint by using method id and '/../'
            $openApiHttpEndpoint = sprintf(
                '/%s/..%s',
                str_replace('/', '-', $method->getIdentifier()),
                $doc->getEndpoint() ?? ''
            );

            $paths[$openApiHttpEndpoint] = ['post' => $operationDoc];
        }

        return $this->appendIfValueHaveSiblings('paths', $paths);
    }

    /**
     * @param HttpServerDoc $doc
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function externalSchemaListArray(HttpServerDoc $doc) : array
    {
        return $this->appendIfValueHaveSiblings(
            'definitions',
            $this->externalSchemaListDocNormalizer->normalize($doc)
        );
    }

    /**
     * @param TagDoc $tag
     *
     * @return array
     */
    private function convertToTagDoc(TagDoc $tag) : array
    {
        $tagArray = ['name' => $tag->getName()];

        $tagArray = $this->appendIfValueNotNull('description', $tag->getDescription(), $tagArray);

        return $tagArray;
    }
}
