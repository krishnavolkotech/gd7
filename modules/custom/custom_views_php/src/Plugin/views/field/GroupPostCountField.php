<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_post_count_field")
 */
class GroupPostCountField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = array('default' => FALSE);
    return $options;
  }
  
  /**
   * {@inheritdoc}
   */
  protected static function groupPostCount($gid) {
    $contents = array(
      'closed_private-group_node-faq',
      'closed_private-group_node-page',
      'closed-group_node-faq',
      'closed-group_node-forum',
      'closed-group_node-page',
      'group_content_type_8fbe33e60f149',
      'moderate_private-group_node-faq',
      'moderate_private-group_node-faqs',
      'group_content_type_466cc368a8c15',
      'group_content_type_aa3a511b55f8c',
      'moderate_private-group_node-page',
      'group_content_type_e340605cbaf90',
      'moderate-group_node-event',
      'moderate-group_node-faq',
      'moderate-group_node-faqs',
      'moderate-group_node-newsletter',
      'moderate-group_node-quickinfo',
      'moderate-group_node-page',
      'group_content_type_b2ed3eb8d19c9',
      'open-group_node-early_warnings',
      'open-group_node-release_comments',
      'group_content_type_ecf0249297413',
      'open-group_node-faq',
      'open-group_node-faqs',
      'open-group_node-planning_files',
      'open-group_node-problem',
      'open-group_node-release',
      'open-group_node-page',
      'open-group_node-downtimes',
    );
    $gpc = \Drupal::database()->select('group_content_field_data')
        ->condition('gid', $gid)
        ->condition('type', $contents, 'IN')
        ->countQuery()
        ->execute()
        ->fetchField();
    // Return the result in object format.
    return $gpc;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $gid = $this->view->field['id']->original_value;
    $result = $this->groupPostCount($gid);
    return $result;
  }

}
