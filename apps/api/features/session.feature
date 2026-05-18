Feature: Email session
  In order to use Social Bulletin
  As a visitor
  I need to register or sign in with just my email address

  Scenario: Registering with a new email creates a user and sets the token cookie
    When I send a POST request to "/api/session" with body:
      """
      {"email": "new.user@example.com"}
      """
    Then the response status code should be 200
    And the JSON at "email" should equal "new.user@example.com"
    And the response should set an httpOnly cookie named "token"
    And exactly one user should exist with email "new.user@example.com"

  Scenario: Signing in with an existing email reuses the user
    Given a user exists with email "existing.user@example.com"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "existing.user@example.com"}
      """
    Then the response status code should be 200
    And the JSON at "email" should equal "existing.user@example.com"
    And the response should set an httpOnly cookie named "token"
    And exactly one user should exist with email "existing.user@example.com"

  Scenario: The current user is returned while the token cookie is valid
    When I send a POST request to "/api/session" with body:
      """
      {"email": "known.user@example.com"}
      """
    And I send a GET request to "/api/me"
    Then the response status code should be 200
    And the JSON at "email" should equal "known.user@example.com"

  Scenario: The current user is unauthorised without a cookie
    When I send a GET request to "/api/me"
    Then the response status code should be 401

  Scenario: Logging out clears the session
    When I send a POST request to "/api/session" with body:
      """
      {"email": "leaving.user@example.com"}
      """
    And I send a POST request to "/api/logout"
    And I send a GET request to "/api/me"
    Then the response status code should be 401

  Scenario: An invalid email is rejected without creating a user
    When I send a POST request to "/api/session" with body:
      """
      {"email": "not-an-email"}
      """
    Then the response status code should be 422
    And the JSON at "message" should not be empty
    And the response should not set a cookie named "token"
    And no user should exist with email "not-an-email"
