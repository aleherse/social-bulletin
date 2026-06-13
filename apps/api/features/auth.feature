Feature: Registration API

  Background:
    Given the database is clean

  Scenario: Register a new user by email
    When I send a POST request to "/api/auth/register" with body:
      """
      {"email":"api@example.com"}
      """
    Then the response status code should be 200
    And the JSON response should match:
      """
      email
      """

  Scenario: Read current user after registration
    When I send a POST request to "/api/auth/register" with body:
      """
      {"email":"me@example.com"}
      """
    And I send a GET request to "/api/auth/me"
    Then the response status code should be 200
