<?php

namespace Drupal\block_upload\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for mymodule blocks.
 *
 * @see \Drupal\block_upload\Plugin\Block\BlockUploadBlock
 */
class BlockUploadBlock extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $blocks_count = \Drupal::state()->get('block_upload_count');
    for ($i = 1; $i <= $blocks_count; $i++) {
      $this->derivatives['block_upload' . $i] = $base_plugin_definition;
      $this->derivatives['block_upload' . $i]['admin_label'] = 'Block upload ' . $i;
      $this->derivatives['block_upload' . $i]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }

}

