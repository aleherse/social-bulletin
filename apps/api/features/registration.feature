Feature: User registration and authentication
  In order to access SocialBulletin
  As a visitor
  I need to register or log in with my email address

  Scenario: Register a new user
    Given I send a POST request to "/api/register" with body:
      """
      {"email": "newuser@example.com"}
      """
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {"email": "newuser@example.com"}
      """
    And the response should set a cookie named "token"

  Scenario: Log in as an existing user
    Given a user with email "existing@example.com" already exists
    When I send a POST request to "/api/register" with body:
      """
      {"email": "existing@example.com"}
      """
    Then the response status code should be 200
    And the response should contain JSON:
      """
      {"email": "existing@example.com"}
      """
    And the response should set a cookie named "token"

  Scenario: Get current user when authenticated
    Given I am authenticated as "auth@example.com"
    When I send a GET request to "/api/me"
    Then the response status code should be 200
    And the response JSON should have key "email" with value "auth@example.com"

  Scenario: Get current user when unauthenticated
    Given I have no authentication cookie
    When I send a GET request to "/api/me"
    Then the response status code should be 401

  Scenario: Logout clears the token cookie
    Given I am authenticated as "logout@example.com"
    When I send a POST request to "/api/logout"
    Then the response status code should be 200
    And the response cookie "token" should be empty
