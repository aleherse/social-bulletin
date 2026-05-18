Feature: API test toolchain
  In order to trust the API test harness
  As a developer
  I need the Symfony test kernel to boot inside Behat

  Scenario: The Symfony test kernel boots
    Then the kernel environment should be "test"
