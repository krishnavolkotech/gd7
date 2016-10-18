<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\cust_group\Controller;

if (!defined('QUICKINFO')) {
  define('QUICKINFO', \Drupal::config('hzd_customizations.settings')->get('quickinfo_group_id'));
}
/**
 *
 */
class DisplaysavedquickinfoController extends ControllerBase {
    
  public function display_saved_quick_info() {
      
     // $is_group_admin = CustNodeController::isGroupAdmin();
      $is_group_member = $this::CheckuserisquickinfoGroupMember();
      
      if ($is_group_member) {
        $group = \Drupal::routeMatch()->getParameter('group');
          if (is_object($group)) {
              $group_id = $group->id();
          } else {
              $group_id = $group;
          }
          $output[]['#attached']['library'] = array(
        //    'locale.libraries/translations',
        //    'locale.libraries/drupal.locale.datepicker',
            'hzd_release_management/hzd_release_management',
            'hzd_customizations/hzd_customizations',
           // 'hzd_release_management/hzd_release_management_sort',
          //  'downtimes/downtimes',
          );
          $output['#attached']['drupalSettings'] = array(
            'group_id' => $group_id,
          );
          $output['#title'] = t("Table of Draft RZ Accelerators");

          $content_type = 'quickinfo';
          $limit = 20;

           $sql = \Drupal::database()->select('node_field_data', 'nfd');
           $sql->Fields('nfd', array('nid', 'title', 'changed', 'uid'));
           $sql->addField('s', 'state');
           $sql->addField('nfui', 'field_unique_id_value');
           $sql->addField('nfrtn', 'field_related_transfer_number_value');   
           $sql->leftJoin('node__field_unique_id', 'nfui', 'nfui.entity_id = nfd.nid');
           $sql->leftJoin('node__field_related_transfer_number', 'nfrtn', 'nfui.entity_id  = nfrtn.entity_id');
           $sql->leftJoin('cust_profile', 'cp', 'cp.uid  = nfd.uid');   
           $sql->leftJoin('states', 's', 's.id  = cp.state_id');
           $sql->condition('nfd.type', $content_type, '=');
           $sql->condition('nfd.status', 0, '=');
         //  $sql->condition('nfui.revision_id', 'nfd.vid', '=');
         //  $sql->condition('nfrtn.revision_id', 'nfd.vid', '=');
           $sql->orderBy('nfd.changed', 'DESC');
           $pager = $sql->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);
           $result = $pager->execute()->fetchAll();

          //$quickinfo_id = array('data' => t('Quickinfo Id'), 'class' => 'quickinfo-hdr');
          $state_name = array('data' => t('State'), 'class' => 'state-hdr');
          $title_name = array('data' => t('Title'), 'class' => 'title-hdr');
          $service_id = array('data' => t('Service ID'), 'class' => 'service-id-hdr');
          $transfer_num = array('data' => t('SW Transfer No.'), 'class' => 'related-transfer-num');
          $published_date = array('data' => t('Last update'), 'class' => 'published-on-hdr');
          $details_name = array('data' => t('Details'), 'class' => 'details-hdr');

          $header = array($state_name, $title_name, $service_id, $transfer_num, $published_date, $details_name);

          // $result = pager_query($sql, $limit);


          $rows = array();
         foreach ($result as $node ) {
            $state_name = $node->state;
            $title  = $node->title;

            $output1 = \Drupal\node\Entity\Node::load($node->nid);
            $other_services = '';
            $content_field = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_other_services');
            $allowed_values = options_allowed_values($content_field);
            foreach($output1->field_other_services as $service_ids) {
              $other_services .= "<div>" . $allowed_values[$service_ids->value] . "</div>";
            }

            $related_sw_transfer_num = $node->field_related_transfer_number_value;
            $published = date('d.m.Y', $node->changed);

            $alias_path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->nid);
            $absolute_path = Url::fromUserInput($alias_path);
            $details = \Drupal::service('link_generator')->generate('Details', $absolute_path);
            // $data = \Drupal\Component\Utility\Html::load($other_services);

            $rows[] = array($state_name, $title, t($other_services), $related_sw_transfer_num, $published,  $details);
         }

         if ($rows) {
              $build['quickinfo_table'] = array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => t('No Data Created Yet'),
                '#attributes' => ['id' => "quickinfo-sortable", 'class' => "tablesorter"],
              );

              $build['pager'] = array(
                '#type' => 'pager',
                '#prefix' => '<div id="pagination">',
                '#suffix' => '</div>',
              );
              return $build;
          }
          return $build = array(
              '#prefix' => '<div id="no-result">',
              '#markup' => t("No Data Created Yet"),
              '#suffix' => '</div>',
              );
      } else {
          return $build = array(
              '#prefix' => '<div id="no-result">',
              '#markup' => t("You are not authorized to view this page"),
              '#suffix' => '</div>',
              );
      }
    }
//    
//     public function CheckisGroupAdmin($group_id = null){
//        if(!$group_id){
//          return false;
//        }
//                    if(in_array('site_administrator',\Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1){
//                            return true;
//                    }
//        $group = \Drupal\group\Entity\Group::load($group_id);
//          $content = $group->getMember(\Drupal::currentUser());
//          if($content){
//              $contentId = $content->getGroupContent()->id();
//              $adminquery = \Drupal::database()->select('group_content__group_roles','gcgr')
//                  ->fields('gcgr',['group_roles_target_id'])->condition('entity_id',$contentId)->execute()->fetchAll();
//              return (bool)!empty($adminquery);
//          }
//			
//    return false;
//  }
  
   static public function CheckuserisquickinfoGroupMember($group_id = null) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        }
        else {
            $group_id = $group;
        }

        if (!$group_id && $group_id != QUICKINFO) {
            return false;
        }
        if (in_array('site_administrator', \Drupal::currentUser()->getRoles()) || \Drupal::currentUser()->id() == 1) {
            return true;
        }
        $group = \Drupal\group\Entity\Group::load($group_id);
        $content = $group->getMember(\Drupal::currentUser());
        if ($content) {
            return true;
        }
        return false;
    }
}
