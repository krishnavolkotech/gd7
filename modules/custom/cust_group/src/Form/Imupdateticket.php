<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class Imupdateticket extends FormBase {
    private static $slug = 0;

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        self::$slug += 1;
        return 'im_update_ticket_' . self::$slug;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $parameter = NULL) {
      $form['ticket_id'] = [
        '#type' => 'textfield',
        '#default_value' => isset($parameter['ticket']) ? $parameter['ticket'] : NULL,
        '#size' => 60,
        ];

      $form['file_id'] = [
          '#type' => 'hidden',
          '#value' => isset($parameter['file_id']) ? $parameter['file_id'] : '',
      ];

      $form['submit'] = [
          '#type' => 'submit',
          '#value' => 'Update'
      ];

      $form['#attributes'] = ['class' => ['hide', 'ticket-update-form']];
      return $form;
    }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

      if (empty(trim($values['ticket_id']))) {
        drupal_set_message($this->t("Please enter ticket id."), 'error');
        $form_state->setErrorByName('ticket_id',t('Please enter ticket id.'));
      }

  }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $ticket_id = empty(trim($form_state->getValue('ticket_id'))) ? NULL : $form_state->getValue('ticket_id');
      $file_id = $form_state->getValue('file_id');
      if (isset($ticket_id) && isset($file_id)) {
        $query = \Drupal::entityQuery('cust_group_imattachments_data');
        $query->condition('fid', $file_id);
        $id = $query->execute();
        $attachment_id = reset($id);
        if ($attachment_id) {
          $imfile = \Drupal\cust_group\Entity\ImAttachmentsData::load($attachment_id);
          $imfile->set('ticket_id', $ticket_id);
          $imfile->save();
          drupal_set_message($this->t("Updated Ticket @ticket successfully.", ['@ticket' => $ticket_id]));
        }
      }
    }
}
    