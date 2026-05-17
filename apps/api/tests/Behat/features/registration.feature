Feature: User Registration

  Background:
    Given the database is clean

  Scenario: Happy path - new user registers successfully
    When I send a POST request to "/api/register" with JSON:
      """
      {
        "email": "alice@example.com",
        "password": "Str0ng!P@ssw0rd",
        "termsAccepted": true
      }
      """
    Then the response status code should be 201
    And the JSON response should contain key "userId"
    And the response should set an httpOnly cookie named "token"

  Scenario: Duplicate email returns 409
    Given a user with email "bob@example.com" already exists
    When I send a POST request to "/api/register" with JSON:
      """
      {
        "email": "bob@example.com",
        "password": "Str0ng!P@ssw0rd",
        "termsAccepted": true
      }
      """
    Then the response status code should be 409
    And the JSON response should contain "error" with "email_already_registered"

  Scenario: Weak password returns 422
    When I send a POST request to "/api/register" with JSON:
      """
      {
        "email": "weak@example.com",
        "password": "123",
        "termsAccepted": true
      }
      """
    Then the response status code should be 422
    And the JSON response should contain a field error for "password"

  Scenario: Terms not accepted returns 422
    When I send a POST request to "/api/register" with JSON:
      """
      {
        "email": "noterms@example.com",
        "password": "Str0ng!P@ssw0rd",
        "termsAccepted": false
      }
      """
    Then the response status code should be 422
    And the JSON response should contain a field error for "termsAccepted"

  Scenario: Authenticated user can access /api/me
    Given I have registered as "carol@example.com" with password "Str0ng!P@ssw0rd"
    When I request "/api/me"
    Then the response status code should be 200
    And the JSON response should contain key "userId"

  Scenario: Unauthenticated request to /api/me returns 401
    When I request "/api/me"
    Then the response status code should be 401
