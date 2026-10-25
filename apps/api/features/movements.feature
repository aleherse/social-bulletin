Feature: Propose a movement
  In order to organise around causes that matter to me
  As a signed-in user
  I need to draft movement proposals and submit them for publication

  Scenario: Creating a draft with every field
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "description": "## Why\nGardens for all.", "category": "cooperative", "area": "municipality", "location": "Sheffield"}}}
      """
    Then the response status code should be 201
    And the response content type should be "application/vnd.api+json"
    And the JSON at "data.type" should equal "movements"
    And the JSON at "data.attributes.status" should equal "draft"
    And the JSON at "data.attributes.title" should equal "Community Gardens for Everyone"
    And the JSON at "data.attributes.category" should equal "cooperative"
    And the JSON at "data.attributes.area" should equal "municipality"
    And the JSON at "data.attributes.location" should equal "Sheffield"
    And the JSON at "data.id" should not be empty

  Scenario: A draft can be saved without a description
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}}}
      """
    Then the response status code should be 201
    And the JSON at "data.attributes.status" should equal "draft"
    And the JSON at "data.attributes.description" should equal ""

  Scenario: An international draft carries no location
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Global Climate Strike", "category": "cooperative", "area": "international"}}}
      """
    Then the response status code should be 201
    And the JSON at "data.attributes.location" should be null

  Scenario: Two users can create movements with the same title
    Given "other@example.com" has a movement draft titled "Community Gardens for Everyone"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}}}
      """
    Then the response status code should be 201

  Scenario: Missing fields are reported per field
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {}}}
      """
    Then the response status code should be 400
    And the JSON at "errors[0].title" should not be empty
    And the JSON at "errors[?source.pointer=='/title'].detail | [0]" should not be empty
    And the JSON at "errors[?source.pointer=='/category'].detail | [0]" should not be empty
    And the JSON at "errors[?source.pointer=='/area'].detail | [0]" should not be empty

  Scenario: An unknown category is rejected
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "category": "not-a-real-category", "area": "municipality", "location": "Sheffield"}}}
      """
    Then the response status code should be 400
    And the JSON at "errors[?source.pointer=='/category'].detail | [0]" should not be empty

  Scenario: A local area requires a location
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality"}}}
      """
    Then the response status code should be 400
    And the JSON at "errors[?source.pointer=='/location'].detail | [0]" should not be empty

  Scenario: Guests cannot create movements
    When I send a POST request to "/api/movements" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}}}
      """
    Then the response status code should be 401
    And the response content type should be "application/vnd.api+json"
    And the JSON at "errors[0].status" should equal "401"

  Scenario: The category list is served in display order
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to "/api/categories"
    Then the response status code should be 200
    And the JSON at "data" should have 4 items
    And the JSON at "data[0].id" should equal "animal_rights"

  Scenario: Users only see their own movements
    Given "other@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to "/api/movements"
    Then the response status code should be 200
    And the JSON at "data" should have 0 items

  Scenario: An author's movements are listed newest first
    Given "author@example.com" has a movement draft titled "Save the Bees"
    And "author@example.com" has a movement draft titled "Community Gardens for Everyone"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to "/api/movements"
    Then the response status code should be 200
    And the JSON at "data" should have 2 items
    And the response content type should be "application/vnd.api+json"
    And the JSON at "data[0].type" should equal "movements"
    And the JSON at "data[0].attributes.title" should equal "Community Gardens for Everyone"
    And the JSON at "data[1].attributes.title" should equal "Save the Bees"

  Scenario: A movement id that is not a UUID is not found
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to "/api/movements/not-a-uuid"
    Then the response status code should be 404
    And the JSON at "errors[0].title" should not be empty

  Scenario: An author can fetch their own movement
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to the movement titled "Save the Bees"
    Then the response status code should be 200
    And the JSON at "data.attributes.title" should equal "Save the Bees"
    And the JSON at "data.attributes.status" should equal "draft"

  Scenario: Another user's movement is not found
    Given "other@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a GET request to the movement titled "Save the Bees"
    Then the response status code should be 404
    And the JSON at "errors[0].title" should not be empty

  Scenario: Submitting a described draft proposes it
    Given "author@example.com" has a movement draft titled "Save the Bees" with a description
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I submit the movement titled "Save the Bees"
    Then the response status code should be 200
    And the JSON at "data.attributes.status" should equal "proposed"

  Scenario: A draft without a description cannot be proposed
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I submit the movement titled "Save the Bees"
    Then the response status code should be 400
    And the JSON at "errors[?source.pointer=='/description'].detail | [0]" should not be empty
    When I send a GET request to the movement titled "Save the Bees"
    Then the JSON at "data.attributes.status" should equal "draft"

  Scenario: A proposed movement cannot be submitted again
    Given "author@example.com" has a proposed movement titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I submit the movement titled "Save the Bees"
    Then the response status code should be 409
    And the JSON at "errors[0].title" should not be empty

  Scenario: Another user's draft cannot be submitted
    Given "other@example.com" has a movement draft titled "Save the Bees" with a description
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I submit the movement titled "Save the Bees"
    Then the response status code should be 404

  Scenario: Editing a draft changes its fields and keeps it a draft
    Given "author@example.com" has a movement draft titled "Save the Bees" with a description
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Save All the Bees", "category": "animal_rights"}}}
      """
    Then the response status code should be 200
    And the JSON at "data.attributes.title" should equal "Save All the Bees"
    And the JSON at "data.attributes.category" should equal "animal_rights"
    And the JSON at "data.attributes.status" should equal "draft"

  Scenario: Editing a draft stamps a later updatedAt without moving createdAt
    Given "author@example.com" has a movement draft titled "Save the Bees" with a description
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Save All the Bees"}}}
      """
    Then the response status code should be 200
    And the movement titled "Save the Bees" should have been updated after it was created

  Scenario: Editing a draft to international clears its location
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"area": "international", "location": null}}}
      """
    Then the response status code should be 200
    And the JSON at "data.attributes.location" should be null

  Scenario: Edits apply the creation validation rules
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": ""}}}
      """
    Then the response status code should be 400
    And the JSON at "errors[?source.pointer=='/title'].detail | [0]" should not be empty

  Scenario: A proposed movement cannot be edited
    Given "author@example.com" has a proposed movement titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Save All the Bees"}}}
      """
    Then the response status code should be 409
    And the JSON at "errors[0].title" should not be empty

  Scenario: Another user's movement cannot be edited
    Given "other@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"data": {"type": "sessions", "attributes": {"email": "author@example.com"}}}
      """
    And I send a PATCH request to the movement titled "Save the Bees" with body:
      """
      {"data": {"type": "movements", "attributes": {"title": "Save All the Bees"}}}
      """
    Then the response status code should be 404
