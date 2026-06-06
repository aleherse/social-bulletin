@e2e
Feature: Email-only registration walking skeleton
  Scenario: A new user registers and receives a JWT cookie
    When I submit registration email "new-user@example.com"
    Then the response status code should be 200
    And the JSON response at "email" should equal "new-user@example.com"
    And the response should set a secure httpOnly "token" cookie

  Scenario: An existing user authenticates by email
    Given a user exists with email "existing-user@example.com"
    When I submit registration email "existing-user@example.com"
    Then the response status code should be 200
    And the JSON response at "email" should equal "existing-user@example.com"
    And the response should set a secure httpOnly "token" cookie

  Scenario: The current user is returned from the JWT cookie
    When I submit registration email "current-user@example.com"
    And I request the current authenticated user
    Then the response status code should be 200
    And the JSON response at "email" should equal "current-user@example.com"
