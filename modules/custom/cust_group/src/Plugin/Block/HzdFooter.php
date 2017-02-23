<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;

/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "hzd_footer",
 *   admin_label = @Translation("Hzd Footer content"),
 *   category = @Translation("Custom Group")
 * )
 */
class HzdFooter extends BlockBase {
  
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $footerHtml = '<div class="row"><span class="cp col-sm-6">©2009-2016 KONSENS</span><span class="footer-links col-sm-6"><a href="#">IMPRESSUM</a> | <a href="#">DATENSCHTZ</a></span></div>
';
    return ['#markup' => $footerHtml];
  }
  
}
