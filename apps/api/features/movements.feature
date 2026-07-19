Feature: Propose a movement
  In order to organise around causes that matter to me
  As a signed-in user
  I need to draft movement proposals and submit them for publication

  Scenario: Creating a draft with every field
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"title": "Community Gardens for Everyone", "description": "## Why\nGardens for all.", "category": "cooperative", "area": "municipality", "location": "Sheffield"}
      """
    Then the response status code should be 201
    And the JSON at "status" should equal "draft"
    And the JSON at "title" should equal "Community Gardens for Everyone"
    And the JSON at "category" should equal "cooperative"
    And the JSON at "area" should equal "municipality"
    And the JSON at "location" should equal "Sheffield"
    And the JSON at "id" should not be empty

  Scenario: A draft can be saved without a description
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}
      """
    Then the response status code should be 201
    And the JSON at "status" should equal "draft"
    And the JSON at "description" should equal ""

  Scenario: An international draft carries no location
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"title": "Global Climate Strike", "category": "cooperative", "area": "international"}
      """
    Then the response status code should be 201
    And the JSON at "location" should be null

  Scenario: Two users can create movements with the same title
    Given "other@example.com" has a movement draft titled "Community Gardens for Everyone"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}
      """
    Then the response status code should be 201

  Scenario: Missing fields are reported per field
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {}
      """
    Then the response status code should be 400
    And the JSON at "message" should not be empty
    And the JSON at "errors.title" should not be empty
    And the JSON at "errors.category" should not be empty
    And the JSON at "errors.area" should not be empty

  Scenario: A local area requires a location
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/movements" with body:
      """
      {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality"}
      """
    Then the response status code should be 400
    And the JSON at "errors.location" should not be empty

  Scenario: Guests cannot create movements
    When I send a POST request to "/api/movements" with body:
      """
      {"title": "Community Gardens for Everyone", "category": "cooperative", "area": "municipality", "location": "Sheffield"}
      """
    Then the response status code should be 401

  Scenario: The category list is served in display order
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a GET request to "/api/categories"
    Then the response status code should be 200
    And the JSON at "categories" should have 4 items
    And the JSON at "categories[0].id" should equal "animal_rights"

  Scenario: Users only see their own movements
    Given "other@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a GET request to "/api/movements"
    Then the response status code should be 200
    And the JSON at "movements" should have 0 items

  Scenario: An author can fetch their own movement
    Given "author@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a GET request to the movement titled "Save the Bees"
    Then the response status code should be 200
    And the JSON at "title" should equal "Save the Bees"
    And the JSON at "status" should equal "draft"

  Scenario: Another user's movement is not found
    Given "other@example.com" has a movement draft titled "Save the Bees"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a GET request to the movement titled "Save the Bees"
    Then the response status code should be 404
    And the JSON at "message" should not be empty
