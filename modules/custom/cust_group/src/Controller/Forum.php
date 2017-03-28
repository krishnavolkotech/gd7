<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 28/3/17
 * Time: 1:10 PM
 */

namespace Drupal\cust_group\Controller;


use Drupal\forum\Controller\ForumController;
use Drupal\group\Entity\GroupInterface;

class Forum extends ForumController {
  public function forum(GroupInterface $group) {
    
    $term = $group->get('field_forum_containers')->referencedEntities()[0];
    $data = parent::forumPage($term);
//    pr($data);exit;
    return $data;
  }
}