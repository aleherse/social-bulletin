Feature: Document the API
  In order to integrate with the API without reading its code
  As an API consumer
  I need an OpenAPI document that matches the JSON:API responses

  Background:
    When I send a GET request to "/api/doc.json"
    Then the response status code should be 200

  Scenario: A single resource response is documented as a jsonapi resource
    Then the OpenAPI document should describe the 200 response of GET "/api/movements/{id}" as a jsonapi resource of "MovementAttributes"
    And the OpenAPI document should describe the 201 response of POST "/api/movements" as a jsonapi resource of "MovementAttributes"

  Scenario: A collection response is documented as a list of JSON:API resources
    Then the OpenAPI document should describe the 200 response of GET "/api/movements" as a jsonapi list of "MovementAttributes"
    And the OpenAPI document should describe the 200 response of GET "/api/categories" as a jsonapi list of "CategoryAttributes"

  Scenario: The response status is the one the controller declares
    Then the OpenAPI document should not describe a 200 response of POST "/api/movements"

  Scenario: The resource id is not repeated in its attributes
    Then the OpenAPI schema "MovementAttributes" should not have a property "id"
    And the OpenAPI schema "CategoryAttributes" should not have a property "id"
    And the OpenAPI schema "MovementAttributes" should have the properties "title, description, category, area, location, status, createdAt, updatedAt"

  Scenario: The signed-in user is documented as a users resource
    Then the OpenAPI document should describe the 200 response of GET "/api/me" as a jsonapi resource of "UserAttributes"
    And the OpenAPI document should describe the 200 response of POST "/api/session" as a jsonapi resource of "UserAttributes"
    And the OpenAPI schema "UserAttributes" should have the properties "email"

  Scenario: Writes document the resource document they accept
    Then the OpenAPI document should describe a "movements" request body for POST "/api/movements"
    And the OpenAPI document should describe a "movements" request body for PATCH "/api/movements/{id}"
    And the OpenAPI document should describe a "sessions" request body for POST "/api/session"

  Scenario: Endpoints document the JSON:API error document
    Then the OpenAPI document should describe the "4XX" error response of GET "/api/movements"
    And the OpenAPI document should describe the "4XX" error response of POST "/api/session"

  Scenario: Request attributes are the fields of the command, without the server-owned ones
    Then the OpenAPI request attributes of POST "/api/movements" should be "title, description, category, area, location"
    And the OpenAPI request attributes of PATCH "/api/movements/{id}" should be "title, description, category, area, location"
    And the OpenAPI request attributes of POST "/api/session" should be "email"

  Scenario: The session cookie is the document's authentication
    Then the OpenAPI document should require the "token" cookie by default
    And the OpenAPI document should not require authentication for POST "/api/session"
    And the OpenAPI document should not require authentication for POST "/api/logout"
    And the OpenAPI document should require authentication for GET "/api/me"

  Scenario: Signing in and out document the cookie they set
    Then the OpenAPI document should say the 200 response of POST "/api/session" sets a cookie
    And the OpenAPI document should say the 204 response of POST "/api/logout" sets a cookie
