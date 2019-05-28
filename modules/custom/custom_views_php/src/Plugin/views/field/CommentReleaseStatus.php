<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\group\Entity\Group;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handler
 *
 * @ViewsField("comment_release_status")
 */
class CommentReleaseStatus extends FieldPluginBase {

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
    if ($values->users_field_data_cust_profile_uid) {
      $rowUser = User::load($values->users_field_data_cust_profile_uid);
      $group = Group::load(RELEASE_MANAGEMENT);
      $groupMember = $group->getMember($rowUser);
      if ($groupMember) {
        $userData = \Drupal::service('user.data');
        $rw_comments_permission = $userData->get('cust_group', $rowUser->id(), 'rw_comments_permission');
        if ($rw_comments_permission) {
          $roles = $groupMember->getRoles();
          if (in_array($group->getGroupType()->id() . '-admin', array_keys($roles))) {
            return Markup::create($this->t(", Release Comments"));
          } else {
            return Markup::create($this->t("Release Comments"));
          }
        }
      }
    }
  }

}
