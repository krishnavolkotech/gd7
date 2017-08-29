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
    $gpc = \Drupal::database()->select('group_content_field_data', 'g');
    $gpc->Join('users_field_data', 'u', 'g.entity_id = u.uid');
    $gpc->leftJoin('inactive_users','iu', 'u.uid = iu.uid');
    $gpc->condition('u.status', 1)
        ->condition('g.gid', $gid)
        //->condition('g.type', $contents, 'IN')
        ->condition('g.type', '%group_node%', 'NOT LIKE')
         ->isNull('iu.uid')
//      ->countQuery()
        ->fields('g');

    $gpc = $gpc->execute()
        ->fetchCol();
    $cc = count($gpc);
    // Return the result in object format.
    return $cc;
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
    $groupMember = $values->_entity->getMember(\Drupal::currentUser());
    if (($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1) || \Drupal::currentUser()->id() == 1) {
      $doc_options['attributes'] = array('class' => 'member-link');
      $url = Url::fromUserInput('/group/' . $gid . '/address', $doc_options);
      $res = \Drupal::service('link_generator')->generate($result, $url);
    }
    return $res;
  }
  
}
