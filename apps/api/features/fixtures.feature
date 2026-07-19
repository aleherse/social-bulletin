@fixtures
Feature: Baseline fixtures
  This feature is excluded from normal test runs (gherkin tag filter) and is
  executed only by `make db` to seed the dataset captured in the DSLR
  `fixtures` snapshot. Keep it to genuinely reusable, representative data;
  scenario-specific state belongs in each scenario's own Given steps.

  Scenario: Load the baseline dataset
    Given the baseline dataset is loaded

  # Movements deliberately have no baseline rows: every movement scenario
  # needs state in a specific status for a specific author, which is
  # scenario-specific by nature. The reusable movement reference data
  # (bulletin.categories) is seeded by the Doctrine migration and is
  # therefore already part of the snapshot.
