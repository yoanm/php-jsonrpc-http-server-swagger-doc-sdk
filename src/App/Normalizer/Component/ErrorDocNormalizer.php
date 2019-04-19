<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcServerDoc\Domain\Model\ErrorDoc;

/**
 * Class ErrorDocNormalizer
 */
class ErrorDocNormalizer
{
    /** @var TypeDocNormalizer */
    private $typeDocNormalizer;
    /** @var ShapeNormalizer */
    private $shapeNormalizer;

    /**
     * @param TypeDocNormalizer $TypeDocNormalizer
     * @param ShapeNormalizer   $shapeNormalizer
     */
    public function __construct(
        TypeDocNormalizer $TypeDocNormalizer,
        ShapeNormalizer $shapeNormalizer
    ) {
        $this->typeDocNormalizer = $TypeDocNormalizer;
        $this->shapeNormalizer = $shapeNormalizer;
    }

    /**
     * @param ErrorDoc $errorDoc
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function normalize(ErrorDoc $errorDoc) : array
    {
        $requiredDoc = ['required' => ['code']];
        $properties = [
            'code' => ['example' => $errorDoc->getCode()]
        ];
        if (null !== $errorDoc->getDataDoc()) {
            $properties['data'] = $this->typeDocNormalizer->normalize($errorDoc->getDataDoc());
            if (false !== $errorDoc->getDataDoc()->isRequired()) {
                $requiredDoc['required'][] = 'data';
            }
        }
        if (null !== $errorDoc->getMessage()) {
            $properties['message'] = ['example' => $errorDoc->getMessage()];
        }

        return [
            'title' => $errorDoc->getTitle(),
            'allOf' => [
                $this->shapeNormalizer->getErrorShapeDefinition(),
                (['type' => 'object'] + $requiredDoc + ['properties' => $properties]),
            ],
        ];
    }
}
