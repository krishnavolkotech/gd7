<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate field widget settings.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldWidgetSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Test that migrated view modes can be loaded using D8 API's.
   */
  public function testWidgetSettings() {
    // Test the config can be loaded.
    $form_display = EntityFormDisplay::load('node.story.default');
    $this->assertIdentical(FALSE, is_null($form_display), "Form display node.story.default loaded with config.");

    // Text field.
    $component = $form_display->getComponent('field_test');
    $expected = [];
    $expected = array('weight' => 1, 'type' => 'text_textfield');
    $expected['settings'] = array('size' => 60, 'placeholder' => '');
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component, 'Text field settings are correct.');

    // Integer field.
    $component = $form_display->getComponent('field_test_two');
    $expected = [];
    $expected['weight'] = 1;
    $expected['type'] = 'number';
    $expected['settings'] = array('placeholder' => '');
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    // Float field.
    $component = $form_display->getComponent('field_test_three');
    $expected = [];
    $expected['weight'] = 2;
    $expected['type'] = 'number';
    $expected['settings'] = array('placeholder' => '');
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    // Email field.
    $component = $form_display->getComponent('field_test_email');
    $expected = [];
    $expected['weight'] = 6;
    $expected['type'] = 'email_default';
    $expected['settings'] = array(
      'placeholder' => '',
      'size' => 60
    );
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    // Link field.
    $component = $form_display->getComponent('field_test_link');
    $this->assertIdentical('link_default', $component['type']);
    $this->assertIdentical(7, $component['weight']);
    $this->assertFalse(array_filter($component['settings']));

    // File field.
    $component = $form_display->getComponent('field_test_filefield');
    $expected = [];
    $expected['weight'] = 8;
    $expected['type'] = 'file_generic';
    $expected['settings'] = array('progress_indicator' => 'bar');
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    // Image field.
    $component = $form_display->getComponent('field_test_imagefield');
    $expected = [];
    $expected['weight'] = 9;
    $expected['settings'] = array('progress_indicator' => 'bar', 'preview_image_style' => 'thumbnail');
    $expected['third_party_settings'] = [];
    $expected['type'] = 'image_image';
    $this->assertIdentical($expected, $component);

    // Phone field.
    $component = $form_display->getComponent('field_test_phone');
    $expected = [];
    $expected['weight'] = 13;
    $expected['type'] = 'telephone_default';
    $expected['settings'] = array('placeholder' => '');
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    // Date fields.
    $component = $form_display->getComponent('field_test_date');
    $expected = [];
    $expected['weight'] = 10;
    $expected['type'] = 'datetime_default';
    $expected['settings'] = array();
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datestamp');
    $expected = [];
    $expected['weight'] = 11;
    $expected['type'] = 'datetime_default';
    $expected['settings'] = array();
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);

    $component = $form_display->getComponent('field_test_datetime');
    $expected = [];
    $expected['weight'] = 12;
    $expected['type'] = 'datetime_default';
    $expected['settings'] = array();
    $expected['third_party_settings'] = [];
    $this->assertIdentical($expected, $component);
  }

}
