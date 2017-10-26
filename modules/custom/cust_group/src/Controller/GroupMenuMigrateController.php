<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;

class GroupMenuMigrateController extends ControllerBase {
  
  function menuMigrate() {
    $query = \Drupal::entityQuery('group')
      ->execute();
    //$query = [31=>31,32=>32];
    $groups = \Drupal\group\Entity\Group::loadMultiple($query);
    foreach ($groups as $group) {
      $data[$group->get('field_old_reference')->value] = $group->id();
    }
    foreach ($data as $old => $new) {
      $menuId = 'menu-' . $old;
      $menuItems = \Drupal::entityQuery('menu_link_content')
        ->condition('menu_name', $menuId)
        ->execute();
      foreach ($menuItems as $item) {
        $menu = MenuLinkContent::load($item);
        //if($item->get('link'))
        $uri = $menu->get('link')->getValue()[0]['uri'];
        if (strpos($uri, '/node/' . $old) !== FALSE) {
          $changedUri = str_replace('/node/' . $old, '/group/' . $new, $uri);
          $menu->set('link', ['uri' => $changedUri])->save();
          //echo $menu->get('link')->getValue()[0]['uri'].'<br>';
        }
        elseif (strpos($uri, '/group/' . $new) !== FALSE) {
          if (strpos($uri, '/group/' . $new . '/members')) {
            $menu->set('link', ['uri' => 'internal:' . '/group/' . $new . '/address'])
              ->save();
          }
          //special case for faq
          if (strpos($uri, '/group/' . $new . '/faq/')) {
            $newFaqPath = $this->migrateFaqPath($uri);
            if ($newFaqPath) {
              $menu->set('link', ['uri' => 'internal:' . $newFaqPath])->save();
            }
//                    echo 'migrate faq'; pr($uri);pr($newFaqPath);
//                    exit;
          }
        }
//        elseif (count(explode('/', trim(str_replace('internal:/', '', $uri), '/'))) == 2) {
//          $newUri = $this->getGroupNodeUri($uri);
//          if ($newUri) {
//            $menu->set('link', ['uri' => 'internal:/' . $newUri])->save();
//          }
//          //echo $uri.'==='.$newUri.'<br>';
//        }
        
      }
      
      /*$menuLink = MenuLinkContent::create([
          'title'      => 'My internal link from a route name',
          'link'       => ['uri' => 'route:myroute'],
          'menu_name'  => 'my-menu',
      ])->save();*/
    }
    pr($data);
    exit;
  }
  
  function getGroupNodeUri($oldUri) {
    $oldNode = explode('/', trim(str_replace('internal:/', '', $oldUri), '/'));
    $node = $oldNode[1];
    $groupContent = \Drupal::entityQuery('group_content')
      ->condition('type', '%group_node%', 'LIKE')
      ->condition('entity_id', $node)
      ->execute();
//      $groupData = \Drupal::database()->select('group_content_field_data','gcfd')
//            ->fields('gcfd',['gid','id'])->condition('entity_id',$node,'=')->execute()->fetchAssoc();
    if (!empty($groupContent)) {
      $groupContentEntity = GroupContent::load(reset($groupContent));
//          pr($groupContentEntity);
      return $groupContentEntity->toUrl()->getInternalPath();
//            return '/group/'.$groupData['gid'].'/node/'.$groupData['id'];
    }
    return FALSE;
    //pr($groupData);exit;
  }
  
  function migrateFaqPath($oldUri) {
    $oldTerms = explode('/', trim(str_replace('internal:/', '', $oldUri), '/'));
    if (empty($oldTerms[3])) {
      $oldTerms[3] = 'Allgemein';
    }
    $group = \Drupal\group\Entity\Group::load($oldTerms[1]);
    
    $parentTermQuery = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $group->label())
      ->condition('vid', 'faq_seite')
      ->execute();
    $termQuery = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $oldTerms[3])
      ->condition('vid', 'faq_seite')
      ->execute();
    $tid = NULL;
    if (empty($parentTermQuery)) {
      $tid = array_values($termQuery)[0];
    }
    if ($termQuery && $parentTermQuery) {
      $tid = \Drupal::database()->select('taxonomy_term_hierarchy', 't')
        ->fields('t', ['tid'])
        ->condition('parent', $parentTermQuery, 'IN')
        ->condition('tid', $termQuery, 'IN')
        ->execute()->fetchCol();
    }
    if ($tid) {
//      pr($tid);exit;
      $termEntity = Term::load(reset($tid)); 
      if($termEntity)
      return '/'.$termEntity->toUrl()->getInternalPath();
    }
    echo 'migrate faq';
    pr($oldUri);
    return FALSE;
  }
  
  
  function deleteOldAlias() {
    $db = \Drupal::database();
    $oldGroups = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadMultiple();
    $aliasDeleted = [];
    foreach ($oldGroups as $group) {
      $id = $group->get('field_old_reference')->value;
      $alais = $db->select('url_alias', 'u')
        ->fields('u', ['source'])
        ->condition('source', '%/node/' . $id . '/%', 'LIKE')
        ->execute()
        ->fetchCol();
      $ids[] = $id;
      $aliasDeleted = array_merge($aliasDeleted, $alais);
      foreach ($alais as $url) {
        $db->delete('url_alias')->condition('source', $url, '=')->execute();
      }
      
    }
    echo 'Old Group Ids:<br>';
    pr($ids);
    echo 'Deleted Aliases:<br>';
    pr($aliasDeleted);
    exit;
  }
  
  function generateFaqPathAlias() {
    $db = \Drupal::database();
    $aliasCleaner = \Drupal::service('pathauto.alias_cleaner');
    $query = \Drupal::entityQuery('group')
      ->execute();
    //$query = [31=>31,32=>32];
    $groups = \Drupal\group\Entity\Group::loadMultiple($query);
    foreach ($groups as $group) {
      $data[$group->get('field_old_reference')->value] = $group->id();
    }
    foreach ($data as $old => $new) {
      $menuId = 'menu-' . $old;
      $menuItems = \Drupal::entityQuery('menu_link_content')
        ->condition('menu_name', $menuId)
        ->execute();
      
      foreach ($menuItems as $item) {
        $menu = MenuLinkContent::load($item);
        
        //if($item->get('link'))
        $uri = $menu->get('link')->getValue()[0]['uri'];
        
        $needle = '/group/' . $new . '/faqs/';
        if (strpos($uri, $needle)) {
          $src = trim(str_replace('internal:/', '', $uri));
          $alias = $db->select('url_alias', 'u')
            ->fields('u', ['source'])
            ->condition('source', '%' . $src . '%', 'LIKE')
            ->execute()
            ->fetchField();
//          pr($alias);exit;
          if (!$alias) {
            $terms = explode('/', trim(str_replace('internal:/', '', $uri), '/'));
            $termEntity = Term::load($terms[3]);
            if ($termEntity) {
              $group = Group::load($terms[1]);
              $path_alias = '/' . $aliasCleaner->cleanString($group->label()) . '/faqs/' . $aliasCleaner->cleanString($termEntity->label());
              \Drupal::service('path.alias_storage')
                ->save('/' . $src, $path_alias);
            }
          }
        }
      }
      
    }
    echo 'Success';
    exit;
  }
  
}
