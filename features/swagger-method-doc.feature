Feature: Swagger specific method doc normalization

  Scenario: Simple method doc normalization
    Check all the path shape
    Given I have an HttpServerDoc
    And I have a MethodDoc with name "method-a"
    And I append last method doc to server doc
    When I normalize server doc
    Then I should have a "POST" path named "/Method-a/.." like following:
    """
    {
      "summary": "\"method-a\" json-rpc method",
      "operationId": "Method-a",
      "consumes": ["application\/json"],
      "produces": ["application\/json"],
      "parameters": [
        {
          "in": "body",
          "name": "JsonRpc request",
          "required": true,
          "schema": {
            "allOf": [
              {
                "type": "object",
                "required": ["jsonrpc", "method"],
                "properties": {
                  "id": {"example": "req_id", "type": "string"},
                  "jsonrpc": {"type": "string", "example": "2.0"},
                  "method": {"type": "string"},
                  "params": {"title": "Method parameters"}
                }
              },
              {
                "type": "object",
                "properties": {
                  "method": {
                    "example": "method-a"
                  }
                }
              }
            ]
          }
        }
      ],
      "responses": {
        "200": {
          "description": "JsonRpc response",
          "schema": {
            "allOf": [
              {
                "type": "object",
                "required": ["jsonrpc"],
                "properties": {
                  "id": {"type": "string", "example": "req_id"},
                  "jsonrpc": {"type": "string", "example": "2.0"},
                  "result": {"title": "Result"},
                  "error": {"title": "Error"}
                }
              },
              {
                "type": "object",
                "properties": {
                  "result": {
                    "description": "Method result"
                  }
                }
              },
              {
                "type": "object",
                "properties": {
                  "error": {
                    "$ref": "#\/definitions\/Default-Error"
                  }
                }
              }
            ]
          }
        }
      }
    }
    """
