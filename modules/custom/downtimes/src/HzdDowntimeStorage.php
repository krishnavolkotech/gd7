<?php

namespace Drupal\downtimes;

/**
 *
 */
class HzdDowntimeStorage {

  /**
   *
   */
  static function insert_group_downtimes_view($selected_services) {
    $counter = 0;
    $query = db_insert('group_downtimes_view');

    $tempstore = \Drupal::service('user.private_tempstore')->get('downtimes');
    $group_id = $tempstore->get('Group_id');

    if ($selected_services) {
      foreach ($selected_services as $service) {
        $counter++;
        $query->fields(array(
          'group_id' => $group_id,
          'service_id' => $service,
        ))->execute();
      }
    }
    return $counter;
  }

  /**
   *
   */
  static function delete_group_downtimes_view() {
    $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    db_delete('group_downtimes_view')->condition('group_id', $group_id, '=')->execute();;
  }

}
