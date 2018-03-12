<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group;

use Drupal\group\Entity\GroupContent;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\GroupContentType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Description of CustGroupHelper
 *
 * @author sandeep
 */
class CustGroupHelper {
  
  //returns the group content id from the node id.
  static function getGroupNodeFromNodeId($nodeId) {

    $node = Node::load($nodeId);

    $plugin_id = 'group_node:' . $node->bundle();

    // Only act if there are group content types for this node type.
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return null;
    }
    // Load all the group content for this node.
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $node->id(),
      ]);
    return reset($group_contents);
  }
  
  public static function getGroupFromRouteMatch() {
    $routeMatch = \Drupal::routeMatch();
    $group = $routeMatch->getParameter('group');
    $node = $routeMatch->getParameter('node');
    
    if (!empty($node) && !$node instanceof Node) {
      $node = Node::load($node);
    }
    if (!empty($node) && empty($group)) {
      $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($node->id());
      if (!empty($groupContent)) {
        $group = $groupContent->getGroup();
      }
    }
    elseif ($term = $routeMatch->getParameter('taxonomy_term')) {
      $storage = \Drupal::service('entity_type.manager')
        ->getStorage('taxonomy_term');
      $parents = $storage->loadParents($term->id());
//        pr($parents);exit;
      if ($routeMatch->getRouteName() == 'forum.page') {
        if (empty($parents)) {
          $parents = $term;
        }
        else {
          $parents = reset($parents);
        }
        $group = \Drupal::service('entity_type.manager')
          ->getStorage('group')
          ->loadByProperties(['field_forum_containers' => $parents->id()]);
      }
      elseif ($routeMatch->getRouteName() == 'entity.taxonomy_term.canonical') {
        if (!(reset($parents) instanceof \Drupal\taxonomy\Entity\Term)) {
          return FALSE;
        }
        $group = \Drupal::service('entity_type.manager')
          ->getStorage('group')
          ->loadByProperties(['label' => reset($parents)->label()]);
      }
      $group = reset($group);
    }
    return $group;
  }


  public static function process($element, FormStateInterface $form_state, $form) {
    $group = \Drupal::request()->get('group',null);
    if(!$group){
      $nodeId = \Drupal::request()->get('node');
      $groupContent = self::getGroupNodeFromNodeId($nodeId->id());
      if($groupContent instanceof GroupContent){
        $group = $groupContent->getGroup();
      }
    }
    $element['imce_paths'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['imce-filefield-paths'],
        'data-imce-url' => Url::fromRoute('cust_group.imce_page', ['group' => $group->id()])->toString(),
      ],
      // Reset value to prevent consistent errors
      '#value' => '',
    ];
    // Library
    $element['#attached']['library'][] = 'imce/drupal.imce.filefield';
    // Set the pre-renderer to conditionally disable the elements.
    $element['#pre_render'][] = ['Drupal\imce\ImceFileField', 'preRenderWidget'];


    //Altering the autocomplete route here
    //cust_group.file_autocomplete
    $element['filefield_reference']['autocomplete']['#autocomplete_route_name'] = 'cust_group.file_autocomplete';
    $element['filefield_reference']['autocomplete']['#autocomplete_route_parameters']['group']=$group;
//    pr($element['filefield_reference']['autocomplete']);exit;

    return $element;
  }
  
}
