<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\group\Entity\Group;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;

if (!defined('QUICKINFO')) {
  define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}

/**
 *
 */
class DisplayquickinfoController extends ControllerBase {

  /**
   * Menu callback to display Quick info content.
   */
  public function display_quick_info() {

    $is_group_member = $this::CheckuserisquickinfoGroupMember();
    if ($is_group_member) {
      $group = \Drupal::routeMatch()->getParameter('group');
      if (is_object($group)) {
        $group_id = $group->id();
      }
      else {
        $group_id = $group;
      }

      // drupal_add_js(drupal_get_path('module', 'release_management') . '/release_management.js');
      // drupal_add_js(drupal_get_path('module', 'hzd_customizations') . '/jquery.tablesorter.min.js');.
               $output[]['#attached']['library'] = array(
        //    'locale.libraries/translations',
        //    'locale.libraries/drupal.locale.datepicker',
            'hzd_release_management/hzd_release_management',
//            'hzd_customizations/hzd_customizations',
           // 'hzd_release_management/hzd_release_management_sort',
          //  'downtimes/downtimes',
          );

          $output['#attached']['drupalSettings'] = array(
            'group_id' => $group_id,
          );

      $output['#title'] = t("Table of RZ Accelerators");

      $url = Url::fromUserInput('/release-management/betriebsueberfuehrung/rz-schnellinfo');
      $link = Link::fromTextAndUrl($this->t('F&uuml;r &auml;ltere Ausgaben (03/2012-02/2014) bitte hier klicken.'), $url);

      $output['#markup'] = '<p>In dieser Übersicht finden Sie RZ-Schnellinfos ab 03/2014. ' . $link . '</p>';
      $output['quickinfo_display_table']['#prefix'] = "<div class = 'quickinfo_content_output'>";
      $output['quickinfo_display_table'] = HzdreleasemanagementHelper::quickinfo_display_table();
      $output['quickinfo_display_table']['#suffix'] = "</div>";
      return $output;
    }
    else {
      return $build = array(
        '#prefix' => '<div id="no-result">',
        '#markup' => t("You are not authorized to view this page"),
        '#suffix' => '</div>',
      );
    }

  }

  /**
   *
   */
  public function CheckuserisquickinfoGroupMember($group_id = NULL) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    $allowed_group = array(QUICKINFO, RELEASE_MANAGEMENT);
    if (!$group_id && !in_array($group_id, $allowed_group)) {
      return FALSE;
    }
    if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
      return TRUE;
    }
    $group = Group::load($group_id);
    $content = $group->getMember(\Drupal::currentUser());
    if ($content && group_request_status($content)) {
      return TRUE;
    }
    return FALSE;
  }

}
