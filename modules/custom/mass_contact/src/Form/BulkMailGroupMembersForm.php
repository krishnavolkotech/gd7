<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\group\Entity\Group;

/**
 * Class BulkMailGroupMembersForm.
 *
 * @package Drupal\mass_contact\Form
 */
class BulkMailGroupMembersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_mail_group_members_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    if (array_intersect($user->getRoles(), [
      'site_administrator',
      'administrator'
    ])) {
      $groups = \Drupal::entityTypeManager()
        ->getStorage('group')
        ->loadByProperties();
      foreach ($groups as $group) {
        $userGroupsAsAdmin[$group->id()] = $group->label();
      }
    }
    else {
      $groupMembershipService = \Drupal::service('group.membership_loader');
      $groupMemberships = $groupMembershipService->loadByUser($user);
      $userGroupsAsAdmin = [];
      foreach ($groupMemberships as $groupMembership) {
        $roles = $groupMembership->getRoles();
        $group = $groupMembership->getGroup();
        if (in_array($group->getGroupType()
            ->id() . '-admin', array_keys($roles))) {
          $userGroupsAsAdmin[$group->id()] = $group->label();
        }
      }
 
    }
    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#options' => (array) $userGroupsAsAdmin
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
    ];
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $gid = $form_state->getValue('group');
    $group = Group::load($gid);
    $subject = $this->t('[@group_name] Newsletter: @subject', ['@group_name' => $group->label(), '@subject' => $form_state->getValue('subject')]);

    $groupMembers = $group->getContent('group_membership');
    foreach ($groupMembers as $user){
      // $user = $groupMember->getGroupContent();
      if($user->getEntity()->isActive() && hzd_user_inactive_status_check($user->getEntity()->id()) == FALSE){
        $mailToGroupMember[] = $user->getEntity()->id();
//          break;
      }
    }

    foreach ($mailToGroupMember as $group_member_id) {
      $operations[] = array(
        '\Drupal\mass_contact\MassMail::sendMail',
        array(
          $group_member_id,
          $subject,
          $form_state->getValue('body')['value']
        )
      );
    }

    //dsm($group_members_list);
    $batch = array(
      'title' => t('Mass mail to group members...'),
      'operations' => $operations,
      'finished' => '\Drupal\mass_contact\MassMail::sendMailFinished',
      'file' => drupal_get_path('module', 'mass_contact') . '/mass_contact_batch.php',
    );
    batch_set($batch);
  }

}
