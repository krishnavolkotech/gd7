<?php

/**
 * @file
 * Contains \Drupal\hzd_notifications\Form\SchnellinfosNotifications
 */

namespace Drupal\hzd_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\Controller\HzdNotifications;
use Drupal\Core\Form\FormCache;
use Drupal\hzd_notifications\HzdNotificationsHelper;
use Drupal\Core\Entity;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

class SchnellinfosNotifications extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'schnellinfos_notifications_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
    //EntityManager::getFieldDefinitions($entity_type_id, $bundle)
    
    //$definition = ContentEntityBase::getFieldDefinition('field_other_services')->getFieldStorageDefinition();
    //$options = options_allowed_values(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL);
    //print_r($options);exit;
    $form['subscriptions'] = array(
      '#type' => 'table',
      '#header' => '',
    );

    /*foreach($content_types as $content_key => $content) {
      $form['subscriptions'][$content_key]['subscriptions_type_' . $content_key] = array(
        '#markup' => $content,
        '#prefix' => "<div class = 'hzd_type'>",
        '#suffix' => "</div>"
      );
      $form['subscriptions'][$content_key]['subscriptions_interval_' . $content_key] = array(
        '#type' => 'radios',
        '#options' => $intervals,
        '#default_value' => '',
        '#prefix' => "<div class = 'hzd_time_interval'>",
        '#suffix' => "</div>"
      );
    }*/
    
    $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      );
      
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  
  }
}
