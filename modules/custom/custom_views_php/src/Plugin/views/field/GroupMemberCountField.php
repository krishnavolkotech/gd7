<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Cache\CacheBackendInterface;
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
    $data = &drupal_static(__FUNCTION__);
    $cid = 'cust_group:membercount';
    $tags = array('config:views.view.all_groups');
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    } else {
      $data = self::get_group_member_count();
      \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }
    return empty($data[$gid]) ? 0 : $data[$gid];
  }

  /**
   * @return mixed
   */
  public static function get_group_member_count() {
    $gpc = \Drupal::database()->select('group_content_field_data', 'g');
    $gpc->Join('users_field_data', 'u', 'g.entity_id = u.uid');
    $gpc->leftJoin('inactive_users', 'iu', 'u.uid = iu.uid');
    $gpc->fields('g', ['gid', 'type']);
    $gpc->addExpression('COUNT(g.type)', 'type_count');
    $gpc->condition('u.status', "1");
    $gpc->groupBy('gid');
    $gpc->groupBy('type');
    // $gpc->condition('g.request_status', "1");
    $gpc->condition('g.type', get_group_content_node_type(), 'NOT IN')
      ->isNull('iu.uid');
    return $gpc->execute()->fetchAllKeyed(0, 2);
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
    if (($groupMember && group_request_status($groupMember)) || \Drupal::currentUser()->id() == 1) {
      $doc_options['attributes'] = array('class' => 'member-link');
      $url = Url::fromUserInput('/group/' . $gid . '/address', $doc_options);
      $res = \Drupal::service('link_generator')->generate($result, $url);
    }
    return $res;
  }

}
