<?php

/*
 * UpdateProblemsNotificationforAllServices
 */

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_notifications\HzdNotificationsHelper;

class UpdateProblemsNotificationforAllServices extends FormBase {

    public function getFormId() {
        return 'update_problem_notification_for_all_services';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => 'Update Problem Notifications'
        ];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        self::prepareBatch();
    }

    static public function prepareBatch() {
        $type = 'problem';
        $data = \Drupal::database()->select('service_notifications_user_default_interval', 'sndi')
                        ->fields('sndi')
                        ->condition('service_type', $type)
                        ->condition('uid', 0, '!=')
                        //->range(0, 20)
                        ->execute()->fetchAll();
        $batch = array(
            'title' => t('Updating problem nofications'),
            'finished' => '\Drupal\cust_group\Form\UpdateProblemsNotificationforAllServices::finishedCallBack',
        );
        foreach ($data as $key => $item) {
            $batch['operations'][] = array(
                '\Drupal\cust_group\Form\UpdateProblemsNotificationforAllServices::update',
                    [$item]
            );
        }
//        pr(count($data));
//        exit;
       return batch_set($batch);
    }

    static public function update($values, &$context) {
        $type = 'problem';
//        $services = hzd_get_all_services();
        //Getting all services except KONSONS
        $rel_type = KONSONS;
        $query = db_select('node_field_data', 'n');
        $query->leftJoin('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
        $query->leftJoin('node__field_problem_name', 'nfpn', 'n.nid = nfpn.entity_id');
        $query->leftJoin('node__field_enable_downtime', 'nfed', 'n.nid = nfed.entity_id');
        $query->leftJoin('node__release_type', 'nrt', 'n.nid = nrt.entity_id');
        $query->condition('n.type', 'services', '=');
        if ($rel_type) {
          $query->condition('nrt.release_type_target_id', $rel_type, '!=');
        }
        $query->fields('n', array('nid'))
          ->fields('nfrn', array('field_release_name_value'))
          ->fields('nfpn', array('field_problem_name_value'))
          ->fields('nrt', array('release_type_target_id'))
          ->fields('nfed', array('field_enable_downtime_value'));
        $services = $query->execute()->fetchAll();
        
        $default_inter = !empty($values->default_send_interval) ? $values->default_send_interval : -1;
        foreach ($services as $service) {
            $data = \Drupal::database()->select('service_notifications', 'sn')
                            ->fields('sn')
                            ->condition('service_id', $service->nid)
                            ->condition('type', $type)
                            ->execute()->fetchAllAssoc('send_interval');
            $uids = [];
            //Skipping individual configured problem services
            $checkId = \Drupal::database()->select('service_notifications_override', 'sno')
                    ->fields('sno', ['sid'])
                    ->condition('service_id', $service->nid)
                    ->condition('type', $type)
                    ->condition('rel_type', $values->rel_type)
                    ->condition('uid', $values->uid)
                    ->execute()
                    ->fetchField();
            if ($checkId) {
                continue;
            }

            $intervals = HzdNotificationsHelper::hzd_notification_send_interval();
            foreach ($intervals as $interval => $val) {
                if (isset($data[$interval])) {
                    $uids = unserialize($data[$interval]->uids);
                    foreach ($uids as $userKey => $item) {
                        if ($item == $values->uid) {
                            unset($uids[$userKey]);
                        }
                    }
                    if ($default_inter == $interval) {
                        $uids[] = $values->uid;
                    }
                    \Drupal::database()
                            ->update('service_notifications')
                            ->fields(['uids' => serialize($uids)])
                            ->condition('sid', $data[$interval]->sid)->execute();
                } else {
                    $notifyData = ['uids' => serialize([$values->uid]),
                        'send_interval' => $interval,
                        'service_id' => $service->nid,
                        'type' => $type];
                    \Drupal::database()
                            ->insert('service_notifications')
                            ->fields($notifyData)->execute();
                }
            }
        }
    }

    public static function finishedCallBack($success, $results, $operations) {
        if ($success) {
            drupal_set_message(\Drupal::translation()->translate('Updated problem notifications successfully'));
        }
    }

}
