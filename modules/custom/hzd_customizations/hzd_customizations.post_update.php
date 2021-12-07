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
