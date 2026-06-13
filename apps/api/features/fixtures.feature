@fixtures
Feature: Database fixture restoration
  In order to have a clean database state for each scenario
  As the test harness
  I need to restore the database snapshot before each scenario

  Scenario: Fixtures are loaded
    Given the database has been restored from the fixtures snapshot
