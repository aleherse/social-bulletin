@fixtures
Feature: Baseline fixtures
  This feature is excluded from normal test runs (gherkin tag filter) and is
  executed only by `make db` to seed the dataset captured in the DSLR
  `fixtures` snapshot. Keep it to genuinely reusable, representative data;
  scenario-specific state belongs in each scenario's own Given steps.

  Scenario: Load the baseline dataset
    Given the baseline dataset is loaded
    And a user exists with email "canadiense.social@malinator.com"
    And "canadiense.social@malinator.com" has a movement draft with:
      | title    | La Canadiense |
      | category | labor         |
      | area     | municipality  |
      | location | Barcelona     |
