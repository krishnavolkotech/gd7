<?php

namespace Drupal\mass_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $uid = $user->id();
    $user_role = $user->getRoles();
    if (!in_array(SITE_ADMIN_ROLE, $user_role)) {
      $group_members_query = db_query("SELECT distinct(gcfd_mem.gid), gcfd_mem.label FROM group_content__group_roles gcgr, group_content_field_data gcfd_mem, group_content_field_data gcfd,
users_field_data ufd WHERE ufd.uid = gcfd_mem.uid AND gcfd_mem.request_status = 1 AND gcfd_mem.gid = gcfd.gid AND gcgr.entity_id = gcfd.id AND gcgr.group_roles_target_id like '%admin%' AND gcfd.entity_id = $uid GROUP BY gcfd_mem.gid, gcfd_mem.label")->fetchAllKeyed();
      $form['group'] = [
        '#type' => 'select',
        '#title' => $this->t('Group'),
        '#options' => (array) $group_members_query
      ];
    }
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

    $form['submit'] = [
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
    $user = \Drupal::currentUser();
    $uid = $user->id();
    $user_role = $user->getRoles();
    if (!in_array(SITE_ADMIN_ROLE, $user_role)) {
      $gid = $form_state->getValue('group');
      $group_members_query = db_query("SELECT distinct(gcfd_mem.uid),ufd.mail FROM group_content_field_data gcfd_mem,users_field_data ufd
 WHERE ufd.uid = gcfd_mem.uid AND ufd.uid <> 0 AND gcfd_mem.request_status = 1 AND gcfd_mem.gid = $gid")->fetchAll();
    }
    else {
      $group_members_query = db_query("SELECT distinct(gcfd_mem.uid),ufd.mail FROM group_content__group_roles gcgr, group_content_field_data gcfd_mem, group_content_field_data gcfd,
        users_field_data ufd WHERE ufd.uid = gcfd_mem.uid AND ufd.uid <> 0 AND gcfd_mem.request_status = 1 AND gcfd_mem.gid = gcfd.gid AND gcgr.entity_id = gcfd.id AND gcgr.group_roles_target_id like '%admin%' AND gcfd.entity_id = $uid")->fetchAll();
    }
    foreach ($group_members_query as $group_members) {
      $group_members_list[] = $group_members->mail;
      $operations[] = array(
        '\Drupal\mass_contact\MassMail::sendMail',
        array($group_members->mail, $form_state->getValue('subject'), $form_state->getValue('body'))
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
