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

class GroupContentViewController extends ControllerBase {
  
  
  /**
   * @param GroupInterface $group
   * @param $type
   * @param GroupContentInterface $groupContent
   */
  public function viewGroupContent(GroupInterface $group, $type, GroupContentInterface $group_content = null) {
    $typeMappings = ['problems'=>'problem','rz-schnellinfos'=>'quickinfo','downtimes'=>'downtimes'];
    if ($group_content->getEntity()->bundle() == $typeMappings[$type]) {
      $view_builder = \Drupal::entityManager()->getViewBuilder($group_content->getEntity()->getEntityTypeId());
      return $view_builder->view($group_content->getEntity(),'full','de');
    } else {
      throw new NotFoundHttpException();
    }
  }
}