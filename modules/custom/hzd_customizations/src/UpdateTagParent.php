<?php

namespace Drupal\hzd_customizations;

use Drupal\node\Entity\Node;

class UpdateTagParent {

  public function get_parent_term($tid) {
    $query = \Drupal::database()->select('taxonomy_term__parent', 'tp');
    $query->addField('tp', 'parent_target_id');
    $query->condition('tp.entity_id', $tid, '=');
    $parent_id = $query->execute()->fetchField();
    return $parent_id;
  }


  public static function updateParent($terms, &$context){
    $message = 'Updating Taxonomy...';
    $results = array();
    foreach ($terms as $term) {
      $actual_parent = \Drupal\hzd_customizations\UpdateTagParent::get_parent_term($term->id());
      if (!$term->parent->target_id && $term->parent->target_id != $actual_parent) {
        $term->parent = ['target_id' => $actual_parent];
        $term->save();
        $results[] = $term->id();
      }
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function updateParentFinishedCallback($success, $results, $operations) {
    if ($success) {
        $message = \Drupal::translation()->formatPlural(
            count($results),
            'One post processed.', '@count posts processed.'
        );
    }
    else {
        $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }
}
