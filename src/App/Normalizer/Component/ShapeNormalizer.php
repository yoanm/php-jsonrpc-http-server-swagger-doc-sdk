<?php
namespace Yoanm\JsonRpcHttpServerSwaggerDoc\App\Normalizer\Component;

/**
 * Class ShapeNormalizer
 */
class ShapeNormalizer
{
    /**
     * @return array
     */
    public function getRequestShapeDefinition()
    {
        return [
            'type' => 'object',
            'required' => ['jsonrpc', 'method'],
            'properties' => [
                'id' => [
                    'example' => 'req_id',
                    'type' => 'string',
                ],
                'jsonrpc' => [
                    'type' => 'string',
                    'example' => '2.0',
                ],
                'method' => ['type' => 'string'],
                'params' => ['title' => 'Method parameters'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getResponseShapeDefinition()
    {
        return [
            'type' => 'object',
            'required' => ['jsonrpc'],
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'example' => 'req_id',
                ],
                'jsonrpc' => [
                    'type' => 'string',
                    'example' => '2.0',
                ],
                'result' => ['title' => 'Result'],
                'error' => ['title' => 'Error'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getErrorShapeDefinition()
    {
        return [
            'type' => 'object',
            'required' => ['code', 'message'],
            'properties' => [
                'code' => ['type' => 'number'],
                'message' => ['type' => 'string'],
            ]
        ];
    }
}
