Feature: Registration walking skeleton API
  Scenario: Create a user and authenticate with a cookie
    When I submit the registration email "new@example.com"
    Then the response status code should be 200
    And the JSON response at "user.email" should be "new@example.com"
    And the response should set an httpOnly token cookie

  Scenario: Read the current authenticated user
    Given a user exists with email "existing@example.com"
    And I am authenticated as "existing@example.com"
    When I request the current user
    Then the response status code should be 200
    And the JSON response at "user.email" should be "existing@example.com"

  Scenario: Logout clears the token cookie
    Given a user exists with email "existing@example.com"
    And I am authenticated as "existing@example.com"
    When I log out
    Then the response status code should be 204
    And the response should clear the token cookie
