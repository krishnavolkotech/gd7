<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handler
 *
 * @ViewsField("group_actions_field")
 */
class GroupActions extends FieldPluginBase {

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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $values->_entity;
    $link = '';
    if($group->getMember(\Drupal::currentUser())){
      $url = Url::fromRoute('entity.group.leave',['group'=>$group->id()]);
      $link = \Drupal::service('link_generator')->generate($this->t('Leave Group'), $url);
    }elseif($group->bundle() == 'open'){
      $url = Url::fromRoute('entity.group.join',['group'=>$group->id()]);
      $link = \Drupal::service('link_generator')->generate($this->t('Join Group'), $url);
    }elseif(in_array($group->bundle(),['moderate','moderate_private'])){
      $url = Url::fromRoute('entity.group.request',['group'=>$group->id()]);
      $link = \Drupal::service('link_generator')->generate($this->t('Request Membership'), $url);
    }elseif(\Drupal::currentUser()->id() == 1){
      $link == 'admin';
    }
    return $link;
  }

}
