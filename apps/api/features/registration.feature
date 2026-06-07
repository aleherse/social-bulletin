@e2e
Feature: Email-only registration walking skeleton
  Scenario: A new user registers and receives a JWT cookie
    When I submit registration email "new-user@example.com"
    Then the response status code should be 200
    And the JSON response at "email" should equal "new-user@example.com"
    And the response should set a secure httpOnly "token" cookie

  Scenario: An existing user authenticates by email
    When I submit registration email "existing-user@example.com"
    Then the response status code should be 200
    And the JSON response at "email" should equal "existing-user@example.com"
    And the response should set a secure httpOnly "token" cookie
