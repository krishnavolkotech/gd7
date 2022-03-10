<?php

namespace Drupal\hzd_release_management\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

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
    $markup[] = Link::createFromRoute($this->t('Störungen und Blockzeiten'), 'downtimes.new_downtimes_controller_newDowntimes', ['group' => INCIDENT_MANAGEMENT]);
    if (\Drupal::currentUser()->id()) {
      $markup[] = Link::createFromRoute($this->t('Störung melden'), 'downtimes.create_downtimes', ['group' => INCIDENT_MANAGEMENT]);
      $markup[] = Link::createFromRoute($this->t('Blockzeit melden'), 'downtimes.create_maintenance', ['group' => INCIDENT_MANAGEMENT]);
      $markup[] = Link::createFromRoute($this->t('Bekannte Fehler und Probleme'), 'problem_management.problems', ['group' => PROBLEM_MANAGEMENT]);
      $markup[] = Link::createFromRoute($this->t('Bereitgestellte Releases'), 'hzd_release_management.released', ['group' => RELEASE_MANAGEMENT]);
      $markup[] = Link::createFromRoute($this->t('RZ-Schnellinfos'), 'view.rz_schnellinfo.page_2', ['arg_0' => RELEASE_MANAGEMENT]);
      $markup[] = Link::fromTextAndUrl($this->t('KONSENS-Glossar'), Url::fromUri('http://glossar.konsens.ktz.testa-de.net', ['attributes' => ['target' => '_blank']]));
      $markup[] = Link::createFromRoute($this->t('Service Monitoring'), 'cust_user.nsm_login');
      $markup[] = Link::createFromRoute($this->t('Architektursteuerung'), 'entity.node.canonical', ['node' => 49812]);
    }
    
    return [
      '#items' => $markup,
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => ['class' => ['menu nav']],
      '#cache' => ['max-age' => 0]
    ];
  }
  
  /**
   *
   */
  public function hzd_quicklinks() {
    $output = "<div class = 'field--name-body'><ul>";
    if (\Drupal::currentUser()->id()) {
      $output .= "<li>" . Link::fromTextAndUrl('Störungen und Blockzeiten', Url::fromUserInput('/group/24/downtimes')) . "</li>\n";
      $output .= "<li>" . Link::fromTextAndUrl('Störung melden', Url::fromUserInput('/group/24/downtimes/create_downtimes')) . "</li>\n";
      // TODO: Need to write access function once group functions work
      // if(quicklink_maintenance_access(32454)) {.
      $output .= "<li>" . Link::fromTextAndUrl('Blockzeit melden', Url::fromUserInput('/group/24/downtimes/create_maintenance')) . "</li>\n";
      // }.
      $output .= "<li>" . Link::fromTextAndUrl('Bekannte Fehler und Probleme', Url::fromUserInput('/group/' . PROBLEM_MANAGEMENT . '/problems')) . "</li>\n";
      $output .= "<li>" . Link::fromTextAndUrl('Bereitgestellte Releases', Url::fromUserInput('/group/' . RELEASE_MANAGEMENT . '/releases')) . "</li>\n";
      $output .= "<li>" . Link::fromTextAndUrl('RZ-Schnellinfos', Url::fromUserInput('/release-management/rz-schnellinfos')) . "</li>\n";
      $output .= "<li><a target=\"_blank\" href=\"http://glossar.konsens.ktz.testa-de.net/\">KONSENS-Glossar</a></li>\n";
      $output .= "<li><a target=\"_blank\" href=\"/login_from_bp\">Service Monitoring</a></li>";
      $output .= "<li>" . Link::fromTextAndUrl('Architektursteuerung', Url::fromUserInput('/architektursteuerung')) . "</li>\n";
    } else {
      $output .= "<li>" . Link::fromTextAndUrl('Störungen und Blockzeiten', Url::fromUserInput('/stoerungen-blockzeiten')) . "</li>\n";
    }
    $output .= "</ul></div>";
    return $output;
  }
  
}
