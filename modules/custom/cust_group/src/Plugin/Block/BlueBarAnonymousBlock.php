<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\BlueBarAnonymousBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;

/**
 * Provides a 'BlueBarAnonymousBlock' block.
 *
 * @Block(
 *   id = "blue_bar_anonymous_block",
 *   admin_label = @Translation("Blue Bar Anonymous block"),
 *   category = @Translation("Custom Group")
 * )
 */
class BlueBarAnonymousBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $html = '<nav aria-labelledby="block-primarylinks-menu" class="contextual-region" id="block-primarylinks" role="navigation">
                <ul class="menu nav">
                  <li style="height: 38px"></li>
                </ul>
                </nav>';

    return ['#title' => '', '#markup' => $html, '#cache' => ['max-age' => 0]];
  }

}
