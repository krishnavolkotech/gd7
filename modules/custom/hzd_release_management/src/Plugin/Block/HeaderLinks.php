<?php

namespace Drupal\hzd_release_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
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
      $output .= Link::createFromRoute($login,'user.logout')->toString() . ' | ';
      $output .= Link::createFromRoute('Mein Profil','entity.user.canonical',['user'=>$uid])->toString() . ' | ';
    }
    else {
      $output .= Link::createFromRoute('Anmelden', 'user.login')->toString() . ' | ';
      $output .= Link::createFromRoute('Registrieren', 'user.register')->toString() . ' | ';
    }
    $output .= Link::fromTextAndUrl(t('Contact'), Url::fromRoute('contact.site_page'))->toString() . ' | ';
    $output .= Link::fromTextAndUrl('Hilfe', Url::fromUserInput('/hilfe'))->toString();
    $output .= "</div>";
    return $output;
  }

}
