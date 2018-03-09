<?php

namespace Drupal\cust_group;

use Drupal\imce\Imce as ImceBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\Group;

/**
 * 
 * 
 * 
 * 
 */
class Imce extends ImceBase{

  public static function processUserFolders(array $folders, AccountProxyInterface $user) {
    $ret = [];
    $request = \Drupal::request();
    $gid = $request->get('group');
    $group = Group::load($gid);
    foreach ($folders as $folder) {
      $path = $folder['path'];
      
      if (static::regularPath($path)) {
        if($path == 'gruppen'){
          $label = \Drupal::service('pathauto.alias_cleaner')->cleanString($group->label());
          $path .= '/'. $label;
        }
        $ret[$path] = $folder;
        unset($ret[$path]['path']);
      }
    }
    return $ret;
  }
}