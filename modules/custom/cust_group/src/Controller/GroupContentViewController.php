<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 1/2/17
 * Time: 6:24 PM
 */

namespace Drupal\cust_group\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GroupContentViewController
 * @package Drupal\cust_group\Controller
 */
class GroupContentViewController extends ControllerBase {
  
  
  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param $type
   * @param \Drupal\group\Entity\GroupContentInterface|NULL $group_content
   * @return array
   */
  public function viewGroupContent(GroupInterface $group, $type, GroupContentInterface $group_content = null) {
    $typeMappings = ['problems'=>'problem','rz-schnellinfos'=>'quickinfo','downtimes'=>'downtimes'];
    if ($group_content->getEntity()->bundle() == $typeMappings[$type]) {
      $view_builder = \Drupal::entityManager()->getViewBuilder($group_content->getEntity()->getEntityTypeId());
      $build = $view_builder->view($group_content->getEntity(),'full','de');
      $title = $group_content->getEntity()->label();
      if($group_content->getEntity()->bundle() == 'downtimes'){
        $downtime = $group_content->getEntity();
        $db = \Drupal::database();
        $downtimeTypeQuery = $db->select('downtimes','d');
        $downtimeTypeQuery->fields('d',['scheduled_p']);
        $downtimeTypeQuery->condition('downtime_id',$downtime->id());
        $downtimeType = $downtimeTypeQuery->execute()->fetchField();
        if($downtimeType == 0){
          $title = $this->t('Störung');
        }else{
          $title = $this->t('Blockzeit');
        }
      }
      $build['#title'] = $title;
      return $build;
    } else {
      throw new NotFoundHttpException();
    }
  }
  
  function viewGroupContentTitle(GroupInterface $group, $type, GroupContentInterface $group_content = null){
    $title = $group_content->getEntity()->label();
    if($group_content->getEntity()->bundle() == 'downtimes'){
      $downtime = $group_content->getEntity();
      $db = \Drupal::database();
      $downtimeTypeQuery = $db->select('downtimes','d');
      $downtimeTypeQuery->fields('d',['scheduled_p']);
      $downtimeTypeQuery->condition('downtime_id',$downtime->id());
      $downtimeType = $downtimeTypeQuery->execute()->fetchField();
      if($downtimeType == 0){
        $title = $this->t('Incident');
      }else{
        $title = $this->t('Maintenance');
      }
    }
    return $title;
  }
  
  function editGroupContent(GroupInterface $group, $type, GroupContentInterface $group_content = null){
    $typeMappings = ['problems'=>'problem','rz-schnellinfos'=>'quickinfo','downtimes'=>'downtimes'];
    if ($group_content->getEntity()->bundle() == $typeMappings[$type]) {
      $form = \Drupal::service('entity.manager')
        ->getFormObject('node', 'edit')
        ->setEntity($group_content->getEntity());
//      $build[] = \Drupal::formBuilder()->getForm($form);
      
      $build = \Drupal::formBuilder()->getForm($form);
      return $build;
    } else {
      throw new NotFoundHttpException();
    }
  }
  
  function editGroupContentTitle(GroupInterface $group, $type, GroupContentInterface $group_content = null){
    return $this->t('Edit @title',['@title'=>$this->viewGroupContentTitle($group,$type,$group_content)]);
  }
}