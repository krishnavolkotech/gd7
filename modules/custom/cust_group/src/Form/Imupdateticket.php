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
        '#required' => TRUE,
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

      $form['#cache'] = ['max-age' => 0];

      $form['#attributes'] = ['class' => ['hide', 'ticket-update-form']];
      return $form;
    }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (empty(trim($values['ticket_id']))) {
      //$form['ticket_id']['#value'] = $form['ticket_id']['#default_value'];
      $form_errors = $form_state->getErrors();
      $form_state->clearErrors();
      $form_errors['ticket_id'] = t('Ticket ID is required');
      foreach ($form_errors as $name => $error_message) {
        $form_state->setErrorByName($name, $error_message);
      }
    }

  }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $ticket_id = empty(trim($form_state->getValue('ticket_id'))) ? NULL : $form_state->getValue('ticket_id');
      // 20200312 #26750 file_id in form_state can be wrong under certain circumstances
      // $file_id = $form_state->getValue('file_id');
      $file_id = \Drupal::request()->request->get('file_id');
      if (isset($ticket_id) && isset($file_id)) {
        $query = \Drupal::entityQuery('cust_group_imattachments_data');
        $query->condition('fid', $file_id);
        $id = $query->execute();
        $attachment_id = reset($id);
        if ($attachment_id) {
          $imfile = \Drupal\cust_group\Entity\ImAttachmentsData::load($attachment_id);
          $imfile->set('ticket_id', $ticket_id);
          $imfile->save();
          \Drupal::messenger()->addMessage($this->t("Updated Ticket @ticket successfully.", ['@ticket' => $ticket_id]));
        }
      }
    }
}
    
