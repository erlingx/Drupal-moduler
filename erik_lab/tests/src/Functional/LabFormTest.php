<?php

namespace Drupal\Tests\erik_lab\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Functional tests for LabForm (the Config API settings form).
 *
 * Functional tests boot a complete Drupal site and test via real HTTP
 * requests.  They are the right choice for forms, routing, and page
 * rendering because they exercise the full Drupal request stack.
 *
 * The dual-container note: the test PHP process and the web-server
 * process share the same database but have separate memory spaces.
 * Config written by a form submit is stored in the DB, so reading it
 * via $this->config() in the test process works correctly.
 *
 * Run:
 *   ddev exec vendor/bin/phpunit modules/custom/erik_lab/tests/src/Functional
 *
 * @group erik_lab
 */
#[RunTestsInSeparateProcesses]
class LabFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * BrowserTestBase installs a complete Drupal site including all
   * config/install files from the listed modules.
   */
  protected static $modules = ['erik_lab'];

  /**
   * Use the minimal Stark theme — avoids pulling in theme dependencies.
   *
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in as a user who has the permission the route requires.
    $admin = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin);
  }

  /**
   * The form route is accessible and renders all expected fields.
   */
  public function testFormRendersExpectedFields(): void {
    $this->drupalGet('/erik-lab/form');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('greeting');
    $this->assertSession()->fieldExists('enable_logging');
    $this->assertSession()->fieldExists('log_channel');
    $this->assertSession()->buttonExists('Save configuration');
  }

  /**
   * Submitting a greeting shorter than 3 characters triggers a field error.
   *
   * Covers: LabForm::validateForm() and Form API #required /
   * field-level errors.
   */
  public function testShortGreetingFailsValidation(): void {
    $this->drupalGet('/erik-lab/form');
    $this->submitForm(['greeting' => 'Hi'], 'Save configuration');

    // The form must show the validation error….
    $this->assertSession()->pageTextContains('Greeting must be at least 3 characters');

    // …and must NOT show the success message.
    $this->assertSession()->pageTextNotContains('The configuration options have been saved');
  }

  /**
   * A valid submission saves all three values to Config API.
   *
   * Covers: LabForm::submitForm(), ConfigFormBase::submitForm() status message,
   * and end-to-end config storage/retrieval.
   */
  public function testValidSubmissionSavesConfig(): void {
    $this->drupalGet('/erik-lab/form');
    $this->submitForm(
      [
        'greeting'       => 'Hello world',
        'enable_logging' => 1,
        'log_channel'    => 'custom_channel',
      ],
      'Save configuration'
    );

    // The parent ConfigFormBase::submitForm() adds this status message.
    $this->assertSession()->pageTextContains('The configuration options have been saved');

    // Verify each value is persisted.  $this->config() reads from the shared
    // test database, so it reflects what the web-server process just saved.
    $config = $this->config('erik_lab.settings');
    $this->assertSame('Hello world', $config->get('greeting'));
    $this->assertTrue((bool) $config->get('enable_logging'));
    $this->assertSame('custom_channel', $config->get('log_channel'));
  }

  /**
   * A user without the required permission receives a 403.
   *
   * Covers: route-level access control (_permission requirement).
   */
  public function testUnprivilegedUserIsDenied(): void {
    $this->drupalLogout();
    $visitor = $this->drupalCreateUser([]);
    $this->drupalLogin($visitor);

    $this->drupalGet('/erik-lab/form');
    $this->assertSession()->statusCodeEquals(403);
  }

}
