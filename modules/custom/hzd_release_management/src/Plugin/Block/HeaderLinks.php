<?php

namespace Drupal\hzd_release_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides header links block.
 *
 * @Block(
 *   id = "hzd_header_links",
 *   admin_label = @Translation("HZD Header Links"),
 *   category = @Translation("Blocks")
 * )
 */
class HeaderLinks extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->hzd_headerlinks(),
      '#cache'=>['max-age'=>0]
    );
  }

  /**
   *
   */
  public function hzd_headerlinks() {
    $output = "<div class = 'field--name-body'>";
    if (\Drupal::currentUser()->id()) {
      $uid = \Drupal::currentUser()->id();
      $name = db_select('cust_profile', 'c');
      $name->addExpression("CONCAT(c.firstname, ' ', c.lastname)", 'full_name');
      $name->condition('c.uid', $uid, '=');
      $results = $name->execute()->fetchField();
      $login = 'Abmelden' . ' (' . $results . ')';
      $output .= \Drupal::l($login, Url::fromUserInput('/user/logout')) . ' | ';
      $output .= \Drupal::l('Mein Profil', Url::fromUserInput('/user')) . ' | ';
    }
    else {
      $output .= \Drupal::l('Anmelden', Url::fromUserInput('/user')) . ' | ';
      $output .= \Drupal::l('Registrieren', Url::fromUserInput('/user/register')) . ' | ';
    }
    $output .= \Drupal::l('Kontakt', Url::fromUserInput('/contact')) . ' | ';
    $output .= \Drupal::l('Hilfe', Url::fromUserInput('/hilfe'));
    $output .= "</div>";
    return $output;
  }

}
