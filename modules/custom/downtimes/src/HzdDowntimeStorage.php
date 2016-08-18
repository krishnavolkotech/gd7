<?php

namespace Drupal\downtimes;

class HzdDowntimeStorage {
  static function insert_group_downtimes_view($selected_services) {

    // $sql = 'insert into {group_problems_view} (group_id, service_id) values (%d, %d)';
    $counter = 0;

    // $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
    // $group_id = $tempstore->get('Group_id');
    $group_id = $_SESSION['Group_id'];

    if (!empty($selected_services)) {

      foreach ($selected_services as $service) {

        $counter++;
        $query = \Drupal::database()->insert('group_downtimes_view')->fields(array(
										  'group_id' => $group_id,
										  'service_id' => $service
										  ))->execute();
	// db_query($sql, $_SESSION['Group_id'], $service);
      }
    }
    return $counter; 

  }

  /**
   *
   */
  static function delete_group_downtimes_view() {
    $query = \Drupal::database()->delete('group_downtimes_view');
	$query->condition('group_id', $_SESSION['Group_id']);
	$query->execute();
  }


}
