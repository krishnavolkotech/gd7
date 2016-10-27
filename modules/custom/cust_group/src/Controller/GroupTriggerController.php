<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\menu_link_content\Entity\MenuLinkContent;


/**
 * Returns responses for Node routes.
 */
class GroupTriggerController extends ControllerBase {

  static function addPageToGroupMenu($groupContent){
    $group = $groupContent->getGroup();
    $menuId = $group->get('field_old_reference')->value;
    $menuLink = MenuLinkContent::create([
                'title'      => $groupContent->label(),
                'link'       => ['uri'=>'internal:/group/'.$group->id().'/node/'.$groupContent->id()],
                'menu_name'  => 'menu-'.$menuId,
            ])->save();
    
  }

}
