Feature: Look up a place name
  In order to fill a movement's location from a point on the map
  As a signed-in author
  I need the name of the place at that point, at the rank my movement's area names

  The fixture places come from fixtures/places.geojsonl, loaded into the snapshot by fixtures.feature.

  Scenario Outline: A point inside a place at the area's rank is named
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": <lat>, "lon": <lon>, "area": "<area>"}
      """
    Then the response status code should be 200
    And the JSON at "name" should equal "<name>"

    Examples:
      | point             | lat     | lon     | area         | name                 |
      | Huelin            | 36.7033 | -4.4366 | national     | España               |
      | Huelin            | 36.7033 | -4.4366 | state        | Andalucía            |
      | Huelin            | 36.7033 | -4.4366 | province     | Málaga               |
      | Huelin            | 36.7033 | -4.4366 | region       | Málaga-Costa del Sol |
      | Huelin            | 36.7033 | -4.4366 | municipality | Málaga               |
      | Huelin            | 36.7033 | -4.4366 | neighborhood | Huelin               |
      | central Sheffield | 53.3811 | -1.4701 | national     | United Kingdom       |
      | central Sheffield | 53.3811 | -1.4701 | municipality | Sheffield            |
      | central Sheffield | 53.3811 | -1.4701 | neighborhood | Kelham Island        |

  Scenario Outline: A point with no place at the area's rank has no name
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": <lat>, "lon": <lon>, "area": "<area>"}
      """
    Then the response status code should be 200
    And the JSON at "name" should be null

    Examples:
      | point                                   | lat     | lon     | area         |
      | central Sheffield, no province boundary | 53.3811 | -1.4701 | province     |
      | south Sheffield, 3.3 km from a quarter  | 53.3600 | -1.4701 | neighborhood |
      | Madrid, outside the fixture places      | 40.4168 | -3.7038 | municipality |
      | the North Atlantic                      | 45.0    | -20.0   | national     |

  Scenario: A latitude out of range is refused without echoing it
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 91.123456, "lon": -4.4366, "area": "national"}
      """
    Then the response status code should be 422
    And the JSON at "errors[?source.pointer=='/lat'].detail | [0]" should not be empty
    And the response body should not contain "91.123456"

  Scenario: A longitude out of range is refused without echoing it
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 36.7033, "lon": 181.654321, "area": "national"}
      """
    Then the response status code should be 422
    And the JSON at "errors[?source.pointer=='/lon'].detail | [0]" should not be empty
    And the response body should not contain "181.654321"

  Scenario: An international area has no rank to look up
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 36.7033, "lon": -4.4366, "area": "international"}
      """
    Then the response status code should be 422
    And the JSON at "errors[?source.pointer=='/area'].detail | [0]" should not be empty

  Scenario: A lookup without an area is refused
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 36.7033, "lon": -4.4366}
      """
    Then the response status code should be 422
    And the JSON at "errors[?source.pointer=='/area'].detail | [0]" should not be empty

  Scenario: A guest cannot look up places
    When I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 36.7033, "lon": -4.4366, "area": "national"}
      """
    Then the response status code should be 401

  Scenario: Importing places from the console replaces the lookup data
    When I run the console command "app:places:import" with the file "fixtures/places-reimport.geojsonl"
    Then the console command should succeed
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 53.3811, "lon": -1.4701, "area": "municipality"}
      """
    Then the JSON at "name" should equal "Sheffield City"
    When I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 36.7033, "lon": -4.4366, "area": "national"}
      """
    Then the JSON at "name" should be null

  Scenario: A malformed import changes nothing
    When I run the console command "app:places:import" with the file "fixtures/places-malformed.geojsonl"
    Then the console command should fail mentioning "line 2"
    When I send a POST request to "/api/session" with body:
      """
      {"email": "author@example.com"}
      """
    And I send a POST request to "/api/places/lookup" with body:
      """
      {"lat": 53.3811, "lon": -1.4701, "area": "municipality"}
      """
    Then the JSON at "name" should equal "Sheffield"
