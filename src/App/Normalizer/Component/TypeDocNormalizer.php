<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

use Yoanm\JsonRpcServerDoc\Domain\Model\Type\ArrayDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\CollectionDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\NumberDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\ObjectDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\StringDoc;
use Yoanm\JsonRpcServerDoc\Domain\Model\Type\TypeDoc;

/**
 * Class TypeDocNormalizer
 */
class TypeDocNormalizer
{
    /** @var SchemaTypeNormalizer */
    private $schemaTypeNormalizer;

    /**
     * @param SchemaTypeNormalizer $schemaTypeNormalizer
     */
    public function __construct(SchemaTypeNormalizer $schemaTypeNormalizer)
    {
        $this->schemaTypeNormalizer = $schemaTypeNormalizer;
    }
    /**
     * @param TypeDoc $doc
     *
     * @return array
     */
    public function normalize(TypeDoc $doc)
    {
        $siblingsDoc = $paramDocRequired = [];

        $siblingsDoc = $this->appendArrayDoc($doc, $siblingsDoc);
        list (
            $siblingsDoc,
            $paramDocRequired
        ) = $this->appendObjectDoc($doc, $siblingsDoc, $paramDocRequired);

        $format = ($doc instanceof StringDoc && null !== $doc->getFormat())
            ? $doc->getFormat()
            : null
        ;

        return $this->appendIfValueNotNull('description', $doc->getDescription())
            + ['type' => $this->schemaTypeNormalizer->normalize($doc)]
            + $this->appendIfValueNotNull('format', $format)
            + ['x-nullable' => ($doc->isNullable() === true)]
            + $paramDocRequired
            + $this->appendIfValueNotNull('default', $doc->getDefault())
            + $this->appendIfValueNotNull('example', $doc->getExample())
            + $this->appendIfValueHaveSiblings('enum', array_values($doc->getAllowedValueList()))
            + $this->getMinMaxDoc($doc)
            + $siblingsDoc
        ;
    }

    /**
     * @param TypeDoc $doc
     * @param $paramDocMinMax
     * @return mixed
     */
    protected function getMinMaxDoc(TypeDoc $doc)
    {
        $paramDocMinMax = [];
        if ($doc instanceof StringDoc) {
            $paramDocMinMax = $this->appendIfValueNotNull('minLength', $doc->getMinLength(), $paramDocMinMax);
            $paramDocMinMax = $this->appendIfValueNotNull('maxLength', $doc->getMaxLength(), $paramDocMinMax);
        } elseif ($doc instanceof NumberDoc) {
            $paramDocMinMax = $this->appendNumberMinMax($doc, $paramDocMinMax);
        } elseif ($doc instanceof ObjectDoc) {
            $paramDocMinMax = $this->appendIfValueNotNull('minProperties', $doc->getMinItem(), $paramDocMinMax);
            $paramDocMinMax = $this->appendIfValueNotNull('maxProperties', $doc->getMaxItem(), $paramDocMinMax);
        } elseif ($doc instanceof CollectionDoc) {
            $paramDocMinMax = $this->appendIfValueNotNull('minItems', $doc->getMinItem(), $paramDocMinMax);
            $paramDocMinMax = $this->appendIfValueNotNull('maxItems', $doc->getMaxItem(), $paramDocMinMax);
        }

        return $paramDocMinMax;
    }

    /**
     * @param TypeDoc $doc
     * @param array   $siblingsDoc
     *
     * @return array
     */
    protected function appendArrayDoc(TypeDoc $doc, array $siblingsDoc)
    {
        // CollectionDoc should be managed as ArrayDoc
        if (!$doc instanceof ArrayDoc && get_class($doc) !== CollectionDoc::class) {
            return $siblingsDoc;
        }
        /** @var $doc ArrayDoc|CollectionDoc */
        // add mandatory "items" field
        if ($doc instanceof ArrayDoc && null !== $doc->getItemValidation()) {
            $siblingsDoc['items'] = $this->normalize($doc->getItemValidation());
        } else {
            $siblingsDoc['items']['type'] = $this->guessItemsType($doc->getSiblingList());
        }

        return $siblingsDoc;
    }

    /**
     * @param TypeDoc $doc
     * @param array   $siblingsDoc
     * @param array   $paramDocRequired
     *
     * @return array
     */
    protected function appendObjectDoc(TypeDoc $doc, array $siblingsDoc, array $paramDocRequired)
    {
        if (!$doc instanceof ObjectDoc) {
            return [$siblingsDoc, $paramDocRequired];
        }

        if (true === $doc->isAllowExtraSibling()) {
            $siblingsDoc['additionalProperties']['description'] = "Extra property";
        }

        $siblingDocList = [];
        $requiredSiblings = [];
        foreach ($doc->getSiblingList() as $sibling) {
            if (true === $sibling->isRequired()) {
                $requiredSiblings[] = $sibling->getName();
            }
            $siblingDocList[$sibling->getName()] = $this->normalize($sibling);
        }

        $paramDocRequired = $this->appendIfValueHaveSiblings('properties', $siblingDocList, $siblingsDoc);
        $paramDocRequired = $this->appendIfValueHaveSiblings('required', $requiredSiblings, $paramDocRequired);

        return [$siblingsDoc, $paramDocRequired];
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param array  $doc
     *
     * @return array
     */
    private function appendIfValueHaveSiblings(string $key, array $value, array $doc = [])
    {
        return $this->appendIf((count($value) > 0), $key, $value, $doc);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param array  $doc
     *
     * @return array
     */
    private function appendIfValueNotNull(string $key, $value, array $doc = [])
    {
        return $this->appendIf((null !== $value), $key, $value, $doc);
    }

    /**
     * @param bool   $doAppend
     * @param string $key
     * @param mixed  $value
     * @param array  $doc
     *
     * @return array
     */
    private function appendIf(bool $doAppend, string $key, $value, array $doc = [])
    {
        if (true === $doAppend) {
            $doc[$key] = $value;
        }

        return $doc;
    }

    /**
     * @param array $siblingList
     *
     * @return string
     */
    protected function guessItemsType(array $siblingList)
    {
        $siblingsType = null;
        foreach ($siblingList as $sibling) {
            $newType = $this->schemaTypeNormalizer->normalize($sibling);
            if (null === $siblingsType) {
                $siblingsType = $newType;
            } else {
                // If contains different types => fallback to string
                if ($siblingsType !== $newType) {
                    $siblingsType = null;
                    break;
                }
            }
        }

        // default string if sub item type not guessable
        return $siblingsType ?? 'string';
    }

    /**
     * @param NumberDoc $doc
     * @param array     $paramDocMinMax
     *
     * @return array
     */
    private function appendNumberMinMax(NumberDoc $doc, array $paramDocMinMax)
    {
        $paramDocMinMax = $this->appendIfValueNotNull('minimum', $doc->getMin(), $paramDocMinMax);
        $paramDocMinMax = $this->appendIf(
            ($doc->getMin() && false === $doc->isInclusiveMin()),
            'exclusiveMinimum',
            true,
            $paramDocMinMax
        );
        $paramDocMinMax = $this->appendIfValueNotNull('maximum', $doc->getMax(), $paramDocMinMax);
        $paramDocMinMax = $this->appendIf(
            ($doc->getMax() && false === $doc->isInclusiveMax()),
            'exclusiveMaximum',
            true,
            $paramDocMinMax
        );

        return $paramDocMinMax;
    }
}
