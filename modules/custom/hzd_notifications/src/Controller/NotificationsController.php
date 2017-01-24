<?php


namespace Drupal\hzd_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Class NotificationsController
 * @package Drupal\hzd_notifications\Controller
 *
 * Lists all unsent notifications.
 */
class NotificationsController extends ControllerBase
{
    
    
    public function listDaily() {
        $primaryKeys = ['service_notifications' => 'sid'];
        $db = \Drupal::database()->select('periodic_notifications', 'pn')
            ->fields('pn')
            ->condition('pn.mail_sent', 0)
            ->execute()->fetchAll();
        foreach ($db as $item) {
            $query = \Drupal::database()->select($item->type, 'base_table')
                ->fields('base_table')
                ->condition($primaryKeys[$item->type] ?: 'id', $item->type_id)
                ->execute()->fetchAssoc();
            $data[] = $query;
        }
//        $db = \Drupal::database()->select('periodic_notifications','pn')
//            ->fields('pn',['type'])
//            ->condition('pn.mail_sent',0)
//            ->execute()->fetchAll();
        pr($data);
        exit;
    }
}