Feature: Realtime tickets
  In order to see changes as they happen
  As a signed-in user
  I need a short-lived ticket to subscribe to my realtime channels

  Scenario: A signed-in user is issued a ticket
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/realtime-tickets"
    Then the response status code should be 201
    And the response content type should be "application/vnd.api+json"
    And the JSON at "data.type" should equal "realtime-tickets"
    And the JSON at "data.id" should not be empty
    And the JSON at "data.attributes.ticket" should not be empty
    And the JSON at "data.attributes.expiresAt" should not be empty
    And the JSON at "data.attributes.endpoint" should not be empty
    And the JSON at "data.attributes.channels" should have 1 items

  Scenario: A visitor is refused a ticket
    When I send a POST request to "/api/realtime-tickets"
    Then the response status code should be 401
