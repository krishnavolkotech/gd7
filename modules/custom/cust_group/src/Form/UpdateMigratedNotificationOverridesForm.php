<?php

namespace Drupal\cust_group\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\HzdNotificationsHelper;

class UpdateMigratedNotificationOverridesForm extends FormBase {
  public function getFormId() {
    return 'update_migrate_notification_overrides';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update Notification'
    ];
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    self::prepareBatch();
  }
  
  
  static public function prepareBatch() {
    $data = \Drupal::database()->select('service_notifications_override','sno')
      ->fields('sno')
      ->execute()->fetchAll();
    
    $batch = array(
      'title' => t('Updating Notification Overrides'),
      'finished' => '\Drupal\cust_group\Form\UpdateMigratedNotificationOverridesForm::finishedCallBack',
    );
    foreach ($data as $item) {
      $batch['operations'][] = array(
        '\Drupal\cust_group\Form\UpdateMigratedNotificationOverridesForm::update',
        [$item]
      );
    }

//    pr(count($data));
//    exit;
    return batch_set($batch);
  }
  
    static public function update($data, &$context) {
        $service = $data->service_id;
        $send_interval = $data->send_interval;
        $uid = $data->uid;
        $type = $data->type;
        HzdNotificationsHelper::hzd_update_content_type_intval($service, $send_interval, $uid, $type, '');
        $context['message'] = t('Updating notifcation overirde of %id', ['%id' => $data->sid]);
    }
  
  
  public static function finishedCallBack($success, $results, $operations) {
    if ($success) {
      drupal_set_message(\Drupal::translation()->translate('Updating Notification overrides completed'));
    }
  }
  
}
