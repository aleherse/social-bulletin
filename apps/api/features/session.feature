Feature: Email session
  In order to use Social Bulletin
  As a visitor
  I need to register or sign in with just my email address

  Scenario: Registering with a new email creates a user and sets the token cookie
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "new.user@example.com"}}}
      """
    Then the response status code should be 200
    And the JSON at "data.attributes.email" should equal "new.user@example.com"
    And the response should set an httpOnly cookie named "token"
    And the "token" cookie should be same-site strict and expire in one hour
    And exactly one user should exist with email "new.user@example.com"

  Scenario: Signing in with an existing email reuses the user
    Given a user exists with email "existing.user@example.com"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "existing.user@example.com"}}}
      """
    Then the response status code should be 200
    And the JSON at "data.attributes.email" should equal "existing.user@example.com"
    And the response should set an httpOnly cookie named "token"
    And exactly one user should exist with email "existing.user@example.com"

  Scenario: The current user is returned while the token cookie is valid
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "known.user@example.com"}}}
      """
    And I send a GET request to "/api/me"
    Then the response status code should be 200
    And the response content type should be "application/vnd.api+json"
    And the JSON at "data.type" should equal "users"
    And the JSON at "data.id" should not be empty
    And the JSON at "data.attributes.email" should equal "known.user@example.com"

  Scenario: A token with less than twenty minutes left is replaced with a fresh cookie
    Given a user exists with email "stale.user@example.com"
    And the request carries a session token for "stale.user@example.com" issued 45 minutes ago
    When I send a GET request to "/api/me"
    Then the response status code should be 200
    And the response should set an httpOnly cookie named "token"
    And the "token" cookie should be same-site strict and expire in one hour

  Scenario: A token with more than twenty minutes left is left alone
    Given a user exists with email "recent.user@example.com"
    And the request carries a session token for "recent.user@example.com" issued 25 minutes ago
    When I send a GET request to "/api/me"
    Then the response status code should be 200
    And the response should not set a cookie named "token"

  Scenario: The current user is unauthorised without a cookie
    When I send a GET request to "/api/me"
    Then the response status code should be 401
    And the response content type should be "application/vnd.api+json"
    And the JSON at "errors[0].title" should not be empty

  Scenario: A token that is not a valid JWT is unauthorised
    Given the request carries a "token" cookie with the value "not-a-jwt"
    When I send a GET request to "/api/me"
    Then the response status code should be 401
    And the response content type should be "application/vnd.api+json"
    And the JSON at "errors[0].status" should equal "401"

  Scenario: Logging out clears the session
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "leaving.user@example.com"}}}
      """
    And I send a POST request to "/api/logout"
    And I send a GET request to "/api/me"
    Then the response status code should be 401

  Scenario: An invalid email is rejected without creating a user
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "not-an-email"}}}
      """
    Then the response status code should be 422
    And the JSON at "errors[0].title" should not be empty
    And the response should not set a cookie named "token"
    And no user should exist with email "not-an-email"

  Scenario: A sign-in must be sent as a JSON:API document
    When I send a POST request to "/api/session" with body:
      """
      {"email": "plain.user@example.com"}
      """
    Then the response status code should be 400
    And the JSON at "errors[0].title" should not be empty
    And no user should exist with email "plain.user@example.com"

  Scenario: A sign-in must declare the sessions resource type
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "movements", "attributes": {"email": "typo.user@example.com"}}}
      """
    Then the response status code should be 409
    And no user should exist with email "typo.user@example.com"

  Scenario: A sign-in sent as plain JSON is refused
    When I send a POST request to "/api/session" as "application/json" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "json.user@example.com"}}}
      """
    Then the response status code should be 415
    And no user should exist with email "json.user@example.com"

  Scenario: A resource id that contradicts the path is refused
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "id": "00000000-0000-7000-8000-000000000000", "attributes": {"title": "Other"}}}
      """
    Then the response status code should be 409

  Scenario: A sign-in whose body is not JSON is refused
    When I send a POST request to "/api/session" with body:
      """
      {"data": 
      """
    Then the response status code should be 400
    And the response content type should be "application/vnd.api+json"
    And the JSON at "errors[0].title" should not be empty

  Scenario: A sign-in whose attributes are not an object is refused
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": "author@example.com"}}
      """
    Then the response status code should be 400
