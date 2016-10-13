<?php

namespace Drupal\hzd_release_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "hzd_quick_links",
 *   admin_label = @Translation("HZD Quick Links"),
 *   category = @Translation("Blocks")
 * )
 */
class QuickLinksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->hzd_quicklinks(),
    );
  }

  /**
   *
   */
  public function hzd_quicklinks() {
    $output = "<div class = 'field--name-body'><ul>";
    if (\Drupal::currentUser()->id()) {
      $output .= "<li>" . \Drupal::l('Störungen und Blockzeiten', Url::fromUserInput('/group/24/downtimes')) . "</li>\n";
      $output .= "<li>" . \Drupal::l('Störung melden', Url::fromUserInput('/group/24/downtimes/create_downtimes')) . "</li>\n";
      // TODO: Need to write access function once group functions work
      // if(quicklink_maintenance_access(32454)) {.
      $output .= "<li>" . \Drupal::l('Blockzeit melden', Url::fromUserInput('/group/24/downtimes/create_maintenance')) . "</li>\n";
      // }.
      $output .= "<li>" . \Drupal::l('Bekannte Fehler und Probleme', Url::fromUserInput('/group/31/problems')) . "</li>\n";
      $output .= "<li>" . \Drupal::l('Bereitgestellte Releases', Url::fromUserInput('/group/32/releases')) . "</li>\n";
      $output .= "<li>" . \Drupal::l('RZ-Schnellinfos', Url::fromUserInput('/release-management/rz-schnellinfos')) . "</li>\n";
      $output .= "<li><a target=\"_blank\" href=\"http://glossar.konsens.ktz.testa-de.net/\">KONSENS-Glossar</a></li>\n";
      $output .= "<li><a target=\"_blank\" href=\"/login_from_bp\">Service Monitoring</a></li>";
      $output .= "<li>" . \Drupal::l('Architektursteuerung', Url::fromUserInput('/architektursteuerung')) . "</li>\n";
    }
    else {
      $output .= "<li>" . \Drupal::l('Störungen und Blockzeiten', Url::fromUserInput('/stoerungen-blockzeiten')) . "</li>\n";
    }
    $output .= "</ul></div>";
    return $output;
  }

}
