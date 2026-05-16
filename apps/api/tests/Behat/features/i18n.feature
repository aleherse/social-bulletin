Feature: Localised error responses
  The API translates operational error messages via the catalogue.

  Scenario: A request to an unknown route returns a translated 404 message
    When I request "/non-existent-path"
    Then the response status code should be 404
    And the JSON response should contain "error" with "The requested resource was not found."
