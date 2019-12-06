<?php

namespace Drupal\hzd_customizations\Controller;

/**
 * @file
 * Contains \Drupal\disc\Controller\DiscBatchController.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of Getgroupids.
 *
 * @author sureshk
 */
class UpdateTaxonomyParent extends ControllerBase {

  public function updateParent() {
    $terms = [];
    $voc = [
      'faq_kategorie',
      'faq_seite',
    ];
        
    foreach ($voc as $vid) {
      $vocabulary_entities = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
      foreach ($vocabulary_entities as $term) {
        $terms[] = $term;
      }
    }
        
    $batch = array(
      'title' => t('Update Taxonomy Parents'),
      'operations' => array(
        array('\Drupal\hzd_customizations\UpdateTagParent::updateParent',  array($terms)),
      ),
      'finished' => '\Drupal\hzd_customizations\UpdateTagParent::updateParentFinishedCallback',
    );
    
    batch_set($batch);
    return batch_process('<front>');
  }
}