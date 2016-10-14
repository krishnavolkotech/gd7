<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_member_count_field")
 */
class GroupMemberCountField extends FieldPluginBase {

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
  protected static function groupMemberCount($gid) {
    $contents = array(
      'closed_private-group_membership',
      'closed-group_membership',
      'downtimes-group_membership',
      'group_content_type_7b308aea24fe7',
      'group_content_type_d4b06e2b6aad0',
      'moderate-group_membership',
      'open-group_membership',
      'quick_info-group_membership',
      'group_content_type_6693a40b54133',
      'group_content_type_c26112f8ad4cd',
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
    $gid = $values->_entity->id();
    $result = $this->groupMemberCount($gid);
    $res = $result;
    if($values->_entity->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1){
      $doc_options['attributes'] = array('class' => 'member-link');
      $url = Url::fromUserInput('/group/' . $gid .'/address', $doc_options);
      $res = \Drupal::service('link_generator')->generate($result, $url);
    }
    return $res;
  }

}
