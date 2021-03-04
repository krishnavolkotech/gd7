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
    $user = \Drupal::currentUser();
    $groupMember = $group->getMember($user);
    if ($groupMember && group_request_status($groupMember)) {
        $roles = $groupMember->getRoles();
            if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
                $link = $this->t('<span title="Gruppenadministratoren können eine Gruppe nicht verlassen"><i>Gruppenadmin</i></span>');
            } else {
                //pr($group->id());exit;
                if (!in_array($group->id(), array("1", "2", "6", "15", "21", "39", "73", "77"))) {
                    $url = Url::fromRoute('entity.group.leave', ['group' => $group->id()]);
                    $link = \Drupal::service('link_generator')->generate($this->t('Leave Group'), $url);
                } else {
                    $link = $this->t('<span title="Gruppenmitgliedschaft ist erforderlich für Kernfunktionalitäten des BpK"><i>Austreten</i></span>');
                }
            }

    } else if(array_intersect(['site_administrator', 'administrator'], $user->getRoles())) {
        if($group->bundle() == 'open'){
          $url = Url::fromRoute('entity.group.join',['group'=>$group->id()]);
          $link = \Drupal::service('link_generator')->generate($this->t('Join Group'), $url);
        }elseif(in_array($group->bundle(),['moderate','moderate_private', 'closed', 'closed_private'])){
          $url = Url::fromRoute('entity.group.group_request_membership',['group'=>$group->id()]);
          $link = \Drupal::service('link_generator')->generate($this->t('Request Membership'), $url);
        }
    }elseif($group->bundle() == 'open'){
      $url = Url::fromRoute('entity.group.join',['group'=>$group->id()]);
      $link = \Drupal::service('link_generator')->generate($this->t('Join Group'), $url);
    } elseif($group->bundle() == 'closed') {
        $link = $this->t('Closed');
    }elseif(in_array($group->bundle(),['moderate','moderate_private'])){
      $url = Url::fromRoute('entity.group.group_request_membership',['group'=>$group->id()]);
      $link = \Drupal::service('link_generator')->generate($this->t('Request Membership'), $url);
    }elseif(\Drupal::currentUser()->id() == 1){
      $link == 'admin';
    }
    return $link;
  }

}
