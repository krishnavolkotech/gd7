<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Returns responses for Node routes.
 */
class FAQListController extends ControllerBase {

  function faqList(){
    $faqTypes = ['closed-group_node-faqs','moderate-group_node-faqs','open-group_node-faqs'];
    $group = \Drupal::routeMatch()->getParameter('group');
    $query = \Drupal::entityQuery('group_content')
      ->condition('type',$faqTypes,'IN')
      ->condition('gid',$group->id())
      ->execute();
    $faqs = \Drupal\group\Entity\GroupContent::loadMultiple($query);
    $view_builder = \Drupal::entityManager()->getViewBuilder('group');
    $list = '';
    foreach($faqs as $faq){
      $faqView = $view_builder->view($faq);
      $list .= \Drupal::service('renderer')->render($faqView);
    }
    return ['#markup'=>$list,'#type'=>'markup'];
  }
  
  function title(){
    $group = \Drupal::routeMatch()->getParameter('group');
    
    return $this->t($group->label().' - Frequently Asked Questions');
  }

}
