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
     */
    public function normalize(HttpServerDoc $doc)
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
     * {@inheritdoc}
     */
    protected function infoArray(HttpServerDoc $doc)
    {
        $infoArray = [];
        $infoArray = $this->appendIfValueNotNull('title', $doc->getName(), $infoArray);
        $infoArray = $this->appendIfValueNotNull('version', $doc->getVersion(), $infoArray);

        return $this->appendIfValueHaveSiblings('info', $infoArray);
    }

    /**
     * {@inheritdoc}
     */
    protected function serverArray(HttpServerDoc $doc)
    {
        $docArray = [];
        $docArray = $this->appendIfValueNotNull('host', $doc->getHost(), $docArray);
        $docArray = $this->appendIfValueNotNull('basePath', $doc->getBasePath(), $docArray);
        $docArray = $this->appendIfValueHaveSiblings('schemes', $doc->getSchemeList(), $docArray);

        return $docArray;
    }

    /**
     * {@inheritdoc}
     */
    protected function tagsArray(HttpServerDoc $doc)
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
     * {@inheritdoc}
     */
    protected function pathsArray(HttpServerDoc $doc)
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
     */
    protected function externalSchemaListArray(HttpServerDoc $doc)
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
    private function convertToTagDoc(TagDoc $tag)
    {
        $tagArray = ['name' => $tag->getName()];

        $tagArray = $this->appendIfValueNotNull('description', $tag->getDescription(), $tagArray);

        return $tagArray;
    }
}
