<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Returns responses for Node routes.
 */
class FAQListController extends ControllerBase {

  function faqList(Group $group, $term = null){
  
//    $group = Group::load($group);
    $parentTermQuery = \Drupal::entityQuery('taxonomy_term')
      ->condition('name',$group->label())
      ->condition('vid','faq_seite')
      ->execute();
    $termQuery = \Drupal::entityQuery('taxonomy_term')
      ->condition('name',$term)
      ->condition('vid','faq_seite')
      ->execute();
    $tid = null;
    if(empty($parentTermQuery)){
      $tid = array_values($termQuery)[0];
    }
    if($termQuery && $parentTermQuery){
      $tid = \Drupal::database()->select('taxonomy_term_hierarchy','t')
        ->fields('t',['tid'])
        ->condition('parent',$parentTermQuery,'IN')
        ->condition('tid',$termQuery,'IN')
        ->execute()->fetchCol();
    }
    
    
    
    $view = \Drupal\views\Views::getView('group_faqs');

// set the display machine id
    $view->setDisplay('page_1');

// set arguments/filter values
    $view->setArguments(array($group->id(), $tid[0]));
  
    return $view->render();
  }
  
  function title(Group $group){
//    $group = Group::load($group);
    return $this->t($group->label().' - Häufig gestellte Fragen');
  }

}
