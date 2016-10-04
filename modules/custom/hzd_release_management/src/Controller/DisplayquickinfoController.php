<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;

/**
 *
 */
class DisplayquickinfoController extends ControllerBase {
    /*
     * menu callback to display Quick info content.
     */

    function display_quick_info() {
      $is_group_admin = CustNodeController::isGroupAdmin();
      $is_group_member = $this::CheckisGroupMember();
      if ($is_group_admin || $is_group_member) {
            $group = \Drupal::routeMatch()->getParameter('group');
            if (is_object($group)) {
                $group_id = $group->id();
            } else {
                $group_id = $group;
            }

            // drupal_add_js(drupal_get_path('module', 'release_management') . '/release_management.js');
            // drupal_add_js(drupal_get_path('module', 'hzd_customizations') . '/jquery.tablesorter.min.js');
            $output['#attachment']['library'] = array(
                  'hzd_release_management/hzd_release_management',
                  );

            $output['#attached']['drupalSettings']['release_management'] = array(
              'group_id' => $group_id,
            );
            $output['#title'] = t("Table of RZ Accelerators");

            $url = Url::fromUserInput('/release-management/betriebsueberfuehrung/rz-schnellinfo');
            $link = \Drupal::l($this->t('F&uuml;r &auml;ltere Ausgaben (03/2012-02/2014) bitte hier klicken.'), $url);

            $output['#markup'] = '<p>In dieser Übersicht finden Sie RZ-Schnellinfos ab 03/2014. ' . $link . '</p>';
            $output['quickinfo_display_table']['#prefix'] = "<div class = 'quickinfo_content_output'>";
            $output['quickinfo_display_table'] = HzdreleasemanagementHelper::quickinfo_display_table();
            $output['quickinfo_display_table']['#suffix'] = "</div>";
            return $output;
        } else {
          return $build = array(
              '#prefix' => '<div id="no-result">',
              '#markup' => t("You are not authorized to view this page"),
              '#suffix' => '</div>',
              );
      }
      
    } 
    
     public function CheckisGroupMember($group_id = null){
        $group = \Drupal::routeMatch()->getParameter('group');
              if (is_object($group)) {
                  $group_id = $group->id();
              } else {
                  $group_id = $group;
              }
        if(!$group_id){
          return false;
        }
        $group = \Drupal\group\Entity\Group::load($group_id);
          $content = $group->getMember(\Drupal::currentUser());
          if($content){
            return true;
          }
        return false;
    }
}
