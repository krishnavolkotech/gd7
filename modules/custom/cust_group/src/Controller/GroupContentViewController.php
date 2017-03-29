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
      $build['#title'] = $group_content->getEntity()->label();
      return $build;
    } else {
      throw new NotFoundHttpException();
    }
  }
}