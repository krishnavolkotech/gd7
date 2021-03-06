<?php

namespace Drupal\simplify\Tests;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test simplify per block-type settings.
 *
 * @group Simplify
 *
 * @ingroup simplify
 */
class PerBlockTypeSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block_content', 'editor', 'simplify'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Simplify per block-type settings test.',
      'description' => 'Test the Simplify per block-type settings.',
      'group' => 'Simplify',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an admin user.
    $admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin_user);

    // Create a block type.
    $this->createBlockContentType('testing_type', TRUE);
  }

  /**
   * Perform full "per block-type" simplify scenario testing.
   */
  public function testSettingSaving() {

    /* -------------------------------------------------------.
     * 0/ Check that everything is here in the block type.
     */
    $this->drupalGet('block/add');

    $this->assertRaw('About text formats', 'Text format option is defined.');
    $this->assertRaw('Revision information', 'Revision option is defined.');

    /* -------------------------------------------------------.
     * 1/ Check if everything is there but unchecked.
     */

    // Globally activate some options.
    $this->drupalGet('admin/config/user-interface/simplify');
    $options = [
      'simplify_admin' => TRUE,
      'simplify_blocks_global[format]' => 'format',
    ];
    $this->drupalPostForm(NULL, $options, $this->t('Save configuration'));
    // Admin users setting.
    $this->assertFieldChecked('edit-simplify-admin', "Admin users can't see hidden fields too.");

    /* -------------------------------------------------------.
     * 2/ Check the effect on block-type settings.
     */

    // Open admin UI.
    $this->drupalGet('admin/structure/block/block-content/manage/testing_type');

    // Blocks.
    $this->assertFieldChecked('edit-simplify-blocks-format', 'Block text format option is checked.');
    $this->assertNoFieldChecked('edit-simplify-blocks-revision-information', 'Block revision information option is not checked.');

    /* -------------------------------------------------------.
     * 2-bis/ Check if everything is properly disabled if needed.
     */

    // Block.
    $text_format = $this->xpath('//input[@name="simplify_blocks[format]" and @disabled="disabled"]');
    $this->assertTrue(count($text_format) === 1, 'Block text format option is disabled.');

    $revision_option = $this->xpath('//input[@name="simplify_block[revision-information]" and @disabled="disabled"]');
    $this->assertTrue(count($revision_option) === 0, 'Block revision information option is not disabled.');

    /* -------------------------------------------------------.
     * 3/ Save some "per block-type" options.
     */

    // Nodes.
    $options = [
      'simplify_blocks[revision_information]' => 'format',
    ];
    $this->drupalPostForm(NULL, $options, $this->t('Save'));

    /* -------------------------------------------------------.
     * 3-bis/ Check if options are saved.
     */
    $this->drupalGet('/admin/structure/block/block-content/manage/testing_type');
    $this->assertFieldChecked('edit-simplify-blocks-revision-information', 'Block revision information option is checked.');

    /* -------------------------------------------------------.
     * 4/ Check The effect of all this on node form.
     */
    $this->drupalGet('block/add/testing_type');

    $this->assertNoRaw('About text formats', 'Text format option is not defined.');
    $this->assertNoRaw('Revision information', 'Revision option is not defined.');
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($label, $create_body = FALSE) {
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => TRUE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

}
