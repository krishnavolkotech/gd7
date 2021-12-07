<?php

namespace Drupal\Tests\queue_mail\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuration of Queue mail module.
 *
 * @group queue_mail
 */
class QueueMailConfigurationTest extends BrowserTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  const CONFIGURATION_PATH = 'admin/config/system/queue_mail';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['queue_mail'];

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests default settings on the settings form.
   */
  public function testDefaultConfiguration() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet(static::CONFIGURATION_PATH);

    $default_values = [
      'queue_mail_keys' => '',
      'queue_mail_queue_time'  => 15,
      'queue_mail_queue_wait_time' => 0,
      'threshold' => 50,
      'requeue_interval' => 10800,
    ];

    foreach ($default_values as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
    }
  }

  /**
   * Tests change of settings.
   */
  public function testChangeConfiguration() {
    $this->drupalLogin($this->adminUser);

    $edit = [
      'queue_mail_keys' => '*',
      'queue_mail_queue_time'  => 60,
      'queue_mail_queue_wait_time' => 15,
      'threshold' => 100,
      'requeue_interval' => 21600,
    ];

    $this->drupalPostForm(static::CONFIGURATION_PATH, $edit, 'Save configuration');

    foreach ($edit as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
    }
  }

  /**
   * Tests "Wait time per item" setting validation.
   */
  public function testWaitTimePerItemValidation() {
    $this->drupalLogin($this->adminUser);

    $validation_text = '"Wait time per item" value can not be bigger than "Queue processing time" value.';

    // "Wait time per item" value is bigger than "Queue processing time" value.
    $edit = [
      'queue_mail_queue_time'  => 30,
      'queue_mail_queue_wait_time' => 35,
    ];
    $this->drupalPostForm(static::CONFIGURATION_PATH, $edit, 'Save configuration');
    $this->assertSession()->responseContains($validation_text);

    // "Wait time per item" value is less than "Queue processing time" value.
    $edit = [
      'queue_mail_queue_time'  => 30,
      'queue_mail_queue_wait_time' => 25,
    ];
    $this->drupalPostForm(static::CONFIGURATION_PATH, $edit, 'Save configuration');
    $this->assertSession()->responseNotContains($validation_text);
  }

}
