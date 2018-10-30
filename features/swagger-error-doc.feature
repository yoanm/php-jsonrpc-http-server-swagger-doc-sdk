Feature: Swagger specific error normalization

  Scenario: Any error must have a default shape
    Given I have an HttpServerDoc
    And I have an ErrorDoc named "my-error" with code 123
    And I append last error doc to server errors
    When I normalize server doc
    Then I should have a normalized definition named "ServerError-My-error123"
    And normalized definition named "ServerError-My-error123" should have a key "allOf" containing:
    """
    {
      "type": "object",
      "required": ["code", "message"],
      "properties": {
        "code": {
          "type": "number"
        },
        "message": {
          "type": "string"
        }
      }
    }
    """

  Scenario: A default error must be always defined
    Given I have an HttpServerDoc
    When I normalize server doc
    Then I should have a normalized definition named "Default-Error"
    And normalized definition named "Default-Error" should be the following:
    """
    {
      "allOf": [
        {
          "type": "object",
          "required": ["code", "message"],
          "properties": {
            "code": {
              "type": "number"
            },
            "message": {
              "type": "string"
            }
          }
        }, {
          "type": "object",
          "properties": {
            "code": {
              "type": "integer"
            }
          }
        }
      ]
    }
    """
