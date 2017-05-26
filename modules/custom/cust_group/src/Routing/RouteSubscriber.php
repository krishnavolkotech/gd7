<?php

namespace Drupal\cust_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\cust_group\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('contact.site_page')) {
      $route->setDefault('_title_callback', "Drupal\cust_group\Controller\CustNodeController::ContactformTitle");
    }
    /*if ($route = $collection->get('view.rz_schnellinfo.page_2')) {
//      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\QuickinfoAccessController::CheckQuickinfoviewAccess");
    }*/

    /*if ($route = $collection->get('view.rz_schnellinfo.page_1')) {
      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\QuickinfoAccessController::CheckQuickinfoviewonlyAccess");
    }*/

    if ($route = $collection->get('entity.node.canonical')) {
//      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\QuickinfoAccessController::CheckQuickinfonodeviewAccess");
      $route->setDefault('_title_callback', "\Drupal\cust_group\Controller\CustNodeController::nodeTitle");
      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\AccessController::downtimeAcces");
    }

    if ($route = $collection->get('entity.group_content.group_node__quickinfo.create_form')) {
      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\QuickinfoAccessController::CheckQuickinfonodecreateAccess");
    }

    if ($route = $collection->get('entity.node.delete_form')) {
      $route->setRequirement('_custom_access', "\Drupal\cust_group\Controller\QuickinfoAccessController::CheckQuickinfonodedeleteAccess");
    }

    // Change render content '/group/{group}/node/{group_node_id}' to '/node/{node}'.// as previous one just renders node title as content
/*    if ($route = $collection->get('entity.group_content.canonical')) {
//      $route->setPath('/group/{group}/node/{group_content}');
        $route->setDefault('_controller', '\Drupal\cust_group\Controller\CustNodeController::groupContentView');
        $route->setDefault('_title_callback', "Drupal\cust_group\Controller\CustNodeController::groupContentTitle");
        $route->setRequirement('group_content', "^[0-9]*$");
//      }
    }*/
/*    if ($route = $collection->get('entity.group_content.group_membership.canonical')) {
      if ($route->getPath() == '/group/{group}/members/{group_content}') {
        $route->setDefault('_controller', '\Drupal\cust_group\Controller\CustNodeController::groupMemberView');
        $route->setDefault('_title_callback', "Drupal\cust_group\Controller\CustNodeController::groupMemberTitle");
      }
    }*/
    if ($route = $collection->get('entity.node.edit_form')) {
      $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::groupNodeEdit');
    }

      if ($route = $collection->get('entity.group_content.delete_form')) {
          $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::groupAdministratorValidation');
      }

    if ($route = $collection->get('entity.group_content.collection')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('entity.group_content.create_page')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('entity.group_content.group_node_add_page')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('entity.group_content.group_node_relate_page')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('entity.group_content.edit_form')) {
      $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::groupContentAccess');
    }
    if ($route = $collection->get('entity.group_content.delete_form')) {
      $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::groupContentAccess');
    }
    if ($route = $collection->get('view.group_members.page_1')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('forum.index')) {
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('entity.group_content.group_node.collection')) {
      // group/{group}/node is the default group content provided by contrib group module we dont need that list any more so just hiding it
      $route->setRequirement('_role', 'administrator');
    }
    if ($route = $collection->get('view.hzd_group_members.pending')) {
      $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::pendingMembersAccess');
    }
    //We have all acces defined seperatly using permissions and custom in few place. I feel this causes issues at sometimes.
/*    foreach ($collection as $key => $route) {
      if (strpos($route->getPath(), '/group/{') === 0 && !in_array($key, ['entity.group_content.group_membership.join_form', 'entity.group.canonical', 'entity.group_content.group_membership.request_membership_form'])) {
        if (!$route->getRequirement('_custom_access'))
          $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\CustNodeController::hzdGroupAccess');
      }
    }*/
/*    if ($route = $collection->get('view.group_members.page_1')) {
      $route->setDefault('_title_callback', "Drupal\cust_group\Controller\AccessController::groupTitle");
    }*/

    // Overriding the controller for front page contrib module
    if ($route = $collection->get('hzd_customizations.front')) {
      $route->setDefault('_controller', "Drupal\cust_group\Controller\FrontPageController::frontPageOverride");
    }
    
    if (($route = $collection->get('user.admin_create')) || ($route = $collection->get('entity.user.collection'))) {
      $route->setRequirement('_custom_access', '\Drupal\cust_group\Controller\AccessController::userCreateAccess');
    }
  }

  //retuns the views related to groups created from UI
  function returnGroupViews() {
    return [
//      'view.group_members.page_1',
//      'view.group_members_lists.page_1',
//      'view.group_content.page_1',
//      'view.group_faqs.page_1',
//      'view.rz_schnellinfo.page_1',
//      'view.rz_schnellinfo.page_2',
    ];
  }

}
