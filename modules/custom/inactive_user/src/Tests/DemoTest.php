<?php

namespace Drupal\inactive_user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal 8 demo module functionality.
 *
 * @group demo
 */
class DemoTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('demo', 'node', 'block');

  /**
   * A simple user with 'access content' permission.
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array('access content'));
  }

  /**
   * Tests the custom form.
   */
  public function testCustomFormWorks() {
    $this->drupalLogin($this->user);
    $this->drupalGet('demo/form');
    $this->assertResponse(200);

    $config = $this->config('demo.settings');
    $this->assertFieldByName('email', $config->get('demo.email_address'), 'The field was found with the correct value.');

    $this->drupalPostForm(NULL, array(
      'email' => 'test@email.com',
    ), t('Save configuration'));
    $this->assertText('The configuration options have been saved.', 'The form was saved correctly.');

    $this->drupalGet('demo/form');
    $this->assertResponse(200);
    $this->assertFieldByName('email', 'test@email.com', 'The field was found with the correct value.');

    $this->drupalPostForm('demo/form', array(
      'email' => 'test@email.be',
    ), t('Save configuration'));
    $this->assertText('This is not a .com email address.', 'The form validation correctly failed.');

    $this->drupalGet('demo/form');
    $this->assertResponse(200);
    $this->assertNoFieldByName('email', 'test@email.be', 'The field was found with the correct value.');
  }

  /**
   * Tests the functionality of the Demo block.
   */
  public function testDemoBlock() {
    $user = $this->drupalCreateUser(array('access content', 'administer blocks'));
    $this->drupalLogin($user);

    $block = array();
    $block['id'] = 'demo_block';
    $block['settings[label]'] = $this->randomMachineName(8);
    $block['theme'] = $this->config('system.theme')->get('default');
    $block['region'] = 'header';
    $edit = array(
      'settings[label]' => $block['settings[label]'],
      'id' => $block['id'],
      'region' => $block['region'],
    );
    $this->drupalPostForm('admin/structure/block/add/' . $block['id'] . '/' . $block['theme'], $edit, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Demo block created.');

    $this->drupalGet('');
    $this->assertText('Hello to no one', 'Default text is printed by the block.');

    $edit = array('settings[demo_block_settings]' => 'Test name');
    $this->drupalPostForm('admin/structure/block/manage/' . $block['id'], $edit, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Demo block saved.');

    $this->drupalGet('');
    $this->assertText('Hello Test name!', 'Configured text is printed by the block.');
  }

}
