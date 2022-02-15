<?php

/**
 * Implements hook_post_update_problems_1
 **/

function hzd_customizations_post_update_problems_1(&$sandbox) {

  $entityTypeManager = \Drupal::entityTypeManager();
  if (!isset($sandbox['progress'])) {
   $query = \Drupal::entityQuery('node');
   $query->condition('type', 'problem');
   $entityIds = $query->execute();

    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['max'] = count($entityIds);
    // $sandbox['final_id'] = array_pop($entityIds);
  }

  // Update in chunks of 100.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'problem')
    ->condition('nid', $sandbox['current_id'], '>')
    ->sort('nid', 'ASC')
    ->range(0, 100);
  $results = $query->execute();

  foreach ($results as $result) {
    $node = $entityTypeManager->getStorage('node')->load($result);

    // Convert field_s_no (int) to field_orp_nr (string)
    $oldNumber = $node->field_s_no->value;
    if ($oldNumber) {
    $StringNewNumber = strval($oldNumber);
    $node->set('field_orp_nr', $StringNewNumber);
    }


    $sandbox['progress']++;
    $sandbox['current_id'] = $result;

    // Creates new revision.
    $node->setNewRevision(TRUE);
    $node->revision_log = 'Update der Problems für ORBIT';
    $node->setRevisionCreationTime(REQUEST_TIME);
    $node->setRevisionUserId(1);

    $node->save();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : $sandbox['progress'] / $sandbox['max'];

  // if ($sandbox['current_id'] == $sandbox['final_id']) {
  //   $sandbox['#finished'] = 1;
  // }

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return $sandbox['progress'] . ' Problems wurden aktualisiert.';
}

/**
 * Deployed Releases post update 1.
 * 
 * Sets the following new fields:
 *  - field_deployment_status
 *  - field_abnormalities_bool
 *  - field_automated_deployment_bool
 *  - field_installation_time
 *  - field_deployed_release
 *  - field_service
 *  - field_prev_release
 *  - field_first_deployment
 *  - field_state_list
 */
function hzd_customizations_post_update_deployed_releases_1(&$sandbox) {

  $entityTypeManager = \Drupal::entityTypeManager();
  if (!isset($sandbox['progress'])) {
   $query = \Drupal::entityQuery('node');
   $query->condition('type', 'deployed_releases');
   $entityIds = $query->execute();

    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['max'] = count($entityIds);
    // $sandbox['final_id'] = array_pop($entityIds);
  }

  // Update in chunks of 100.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'deployed_releases')
    ->condition('nid', $sandbox['current_id'], '>')
    ->sort('nid', 'ASC')
    ->range(0, 200);
  $results = $query->execute();

  $statusMap = [
    '1' => '2',
    '2' => '1',
  ];

  $boolMap = [
    '1' => 1,
    '2' => 0,
  ];

  foreach ($results as $result) {
    $node = $entityTypeManager->getStorage('node')->load($result);

    // Convert field_archived_release to field_deployment_status.
    $oldStatus = $node->field_archived_release->value;
    $oldStatus = $oldStatus ?: '2';
    $node->set('field_deployment_status', $statusMap[$oldStatus]);
  
    // Convert field_abnormalities to field_abnormalities_bool.
    $oldAbnormalities = $node->field_abnormalities->value;
    $oldAbnormalities = $oldAbnormalities ?: '2';
    $node->set('field_abnormalities_bool', $boolMap[$oldAbnormalities]);

    // Convert field_automated_deployment to field_automated_deployment_bool.
    $oldAutomatedDeployment = $node->field_automated_deployment->value;
    $oldAutomatedDeployment = $oldAutomatedDeployment ?: '2';
    $node->set('field_automated_deployment_bool', $boolMap[$oldAutomatedDeployment]);

    // Convert field_installation_duration to field_installation_time.
    $oldMinutesValue = $node->field_installation_duration->value;
    if ($oldMinutesValue) {
      $explodedValue = explode(':', $oldMinutesValue);
      $hours = intval(array_shift($explodedValue));
      $minutes = intval($explodedValue[0]) + $hours * 60;
      $node->set('field_installation_time', $minutes);
    }

    // Convert field_earlywarning_release to field_deployed_release.
    $oldRelease = $node->field_earlywarning_release->value;
    if ($oldRelease) {
      $node->set('field_deployed_release', $oldRelease);
    }

    // Convert field_release_service to field_service.
    $oldService = $node->field_release_service->value;
    if ($oldService) {
      $node->set('field_service', $oldService);
    }

    // Convert field_previous_release to field_prev_release.
    $oldPrevRelease = $node->field_previous_release->value;
    if ($oldPrevRelease) {
      $node->set('field_prev_release', $oldPrevRelease);
    }

    // Identify first deployment status from field_previous_release.
    if ($oldPrevRelease && $oldPrevRelease > 0) {
      $node->set('field_first_deployment', 0);
    }
    else {
      $node->set('field_first_deployment', 1);
    }

    // Convert field_user_state to field_state_list.
    $oldUserStateId = $node->field_user_state->value;
    if ($oldUserStateId) {
      $node->set('field_state_list', $oldUserStateId);
    }


    $sandbox['progress']++;
    $sandbox['current_id'] = $result;

    // Creates new revision.
    // $node->setNewRevision(TRUE);
    // $node->revision_log = 'Feldaktualisierung.';
    // $node->setRevisionCreationTime(REQUEST_TIME);
    // $node->setRevisionUserId(1);
    $changed = $node->getChangedTime();
    $node->changed = $changed;

    $node->save();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : $sandbox['progress'] / $sandbox['max'];

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return $sandbox['progress'] . ' Einsatzmeldungen wurden aktualisiert.';
}


/**
 * Implements hook_post_update_NAME.
 * Early Warnings post update 1.
 */
function hzd_customizations_post_update_early_warnings_1(&$sandbox) {

  $entityTypeManager = \Drupal::entityTypeManager();
  if (!isset($sandbox['progress'])) {
   $query = \Drupal::entityQuery('node');
   $query->condition('type', 'early_warnings');
   $entityIds = $query->execute();

    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['max'] = count($entityIds);
  }

  // Update in chunks of 20.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'early_warnings')
    ->condition('nid', $sandbox['current_id'], '>')
    ->sort('nid', 'ASC')
    ->range(0, 200);
  $results = $query->execute();

   foreach ($results as $result) {
    $node = $entityTypeManager->getStorage('node')->load($result);

    // Convert field_earlywarning_release to field_release_ref.
    $oldRelease = $node->field_earlywarning_release->value;
    if ($oldRelease) {
      $node->set('field_release_ref', $oldRelease);
    }

    // Convert field_release_service to field_service.
    $oldService = $node->field_release_service->value;
    if ($oldService) {
      $node->set('field_service', $oldService);
    }

    $sandbox['progress']++;
    $sandbox['current_id'] = $result;

    // Creates new revision.
    // $node->setNewRevision(TRUE);
    // $node->revision_log = 'Feldaktualisierung.';
    // $node->setRevisionCreationTime(REQUEST_TIME);
    // $node->setRevisionUserId(1);
    $changed = $node->getChangedTime();
    $node->changed = $changed;
    $node->save();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : $sandbox['progress'] / $sandbox['max'];

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return $sandbox['progress'] . ' Early Warnings wurden aktualisiert.';
}

/**
 * Implements hook_post_update_NAME.
 * Release comments post update 1.
 */
function hzd_customizations_post_update_release_comments_1(&$sandbox) {

  $entityTypeManager = \Drupal::entityTypeManager();
  if (!isset($sandbox['progress'])) {
   $query = \Drupal::entityQuery('node');
   $query->condition('type', 'release_comments');
   $entityIds = $query->execute();

    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['max'] = count($entityIds);
  }

  // Update in chunks of 20.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'release_comments')
    ->condition('nid', $sandbox['current_id'], '>')
    ->sort('nid', 'ASC')
    ->range(0, 200);
  $results = $query->execute();

    foreach ($results as $result) {
    $node = $entityTypeManager->getStorage('node')->load($result);

    // Convert field_earlywarning_release to field_release_ref.
    $oldRelease = $node->field_earlywarning_release->value;
    if ($oldRelease) {
      $node->set('field_release_ref', $oldRelease);
    }

    // Convert field_release_service to field_service.
    $oldService = $node->field_release_service->value;
    if ($oldService) {
      $node->set('field_service', $oldService);
    }

    $sandbox['progress']++;
    $sandbox['current_id'] = $result;

    // Creates new revision.
    // $node->setNewRevision(TRUE);
    // $node->revision_log = 'Feldaktualisierung.';
    // $node->setRevisionCreationTime(REQUEST_TIME);
    // $node->setRevisionUserId(1);
    $changed = $node->getChangedTime();
    $node->changed = $changed;

    $node->save();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : $sandbox['progress'] / $sandbox['max'];

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return $sandbox['progress'] . ' Release Kommentare wurden aktualisiert.';
}
