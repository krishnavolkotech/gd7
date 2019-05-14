<?php

namespace Drupal\custom_script_batch\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class UpdateTermController.
 */
class UpdateTermController extends ControllerBase {

  /**
   * Update_term.
   *
   * @return string
   *   Return Hello string.
   */
  public function update_term() {
    $voc = [
      'faq_kategorie',
      'forums',
      'faq_seite',
      'release_type',
      'tags',
      'thema',
    ];

    foreach ($voc as $vid) {
      dump($vid);
      $vocabulary_entities = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
      foreach ($vocabulary_entities as $term) {
        $term->Save();
      }
      drupal_set_message('Updated Terms for @vid successfully.', ['@vid' => $vid]);
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Terms Updated Successfully')
    ];
  }

}
