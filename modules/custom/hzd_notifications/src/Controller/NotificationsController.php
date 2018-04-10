<?php


namespace Drupal\hzd_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationsController
 * @package Drupal\hzd_notifications\Controller
 *
 * Lists all unsent notifications.
 */
class NotificationsController extends ControllerBase{


    public function __construct(Connection $connection){
        $this->connection = $connection;
    }

    public static function create(ContainerInterface $container){
        return new static($container->get('database'));
    }
    public function listScheduledData(){
        $connection = $this->connection;
        $fields = [
        'sid',
        'entity_id',
        'entity_type',
        'bundle',
        'action',
        'user_data',
        // 'body',
        'subject',
        ];

        $query = $connection->select(NOTIFICATION_SCHEDULE_TABLE, 'ns');
        $notifications = $query->fields('ns', $fields)
        ->execute()->fetchAllAssoc('sid', \PDO::FETCH_ASSOC);
        $headers = [
            'sid','entity_id', 'entity_type', 'bundle', 'action', 'users', 'subject'
        ];
        return ['#type' => 'table', '#header' => $headers, '#rows' => $notifications];
    }
}