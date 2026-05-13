Feature: Health check
  Scenario: API reports health
    When I request "/health"
    Then the response status code should be 200
    And the JSON response should contain "status" with "ok"
