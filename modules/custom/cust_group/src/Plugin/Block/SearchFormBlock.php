<?php

/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\GroupMenuBlock.
 */

namespace Drupal\cust_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Url;

/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "cust_group_search_block",
 *   admin_label = @Translation("Custom Group Search block"),
 *   category = @Translation("Custom Group")
 * )
 */
class SearchFormBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\cust_group\Form\SearchForm');
//    $form['op']['#access'] = FALSE;
    $form['form_build_id']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['#cache']['max-age'] = 0;
//    $form['op']['#access'] = 0;
    return $form;
    }
}
