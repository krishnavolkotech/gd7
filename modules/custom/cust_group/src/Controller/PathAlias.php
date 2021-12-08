<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of PathAlias
 *
 * @author sandeep
 */
class PathAlias extends ControllerBase {

  //put your code here
  static function bulkUpdate() {
    $groupContentIds = \Drupal::entityQuery('group_content')->condition('type', get_group_content_node_type(), 'IN')->execute();
    //pr($groupContentIds);exit;
    //$groupContent = \Drupal\group\Entity\GroupContent::loadMultiple($groupContentIds);
    $batch = array(
      'title' => t('Bulk updating Group Content URL aliases'),
      'operations' => array(
        array('Drupal\cust_group\Controller\PathAlias::batchStart', array()),
      ),
      'finished' => 'Drupal\cust_group\Controller\PathAlias::batchFinished',
    );
    foreach ($groupContentIds as $entity) {
      if (!empty($entity)) {
        $batch['operations'][] = array('Drupal\cust_group\Controller\PathAlias::batchProcess', array($entity));
      }
    }
    batch_set($batch);
    return batch_process('user');
  }

  static function batchProcess($id) {
    $entity = \Drupal\group\Entity\GroupContent::load($id);
    if ($entity instanceof \Drupal\group\Entity\GroupContent) {
      try {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $aliasCleaner = \Drupal::service('pathauto.alias_cleaner');
        $groupTitle = $entity->getGroup()->label();
        if ($entity->getEntity()) {
          $contentLabel = $entity->getEntity()->label();
          $path_alias = '/' . $aliasCleaner->cleanString($groupTitle) . '/' . $aliasCleaner->cleanString($contentLabel);
          \Drupal::service('path_alias.storage')->save('/' . $entity->toUrl()->getInternalPath(), $path_alias, 'de');
        }
      } catch (Exception $excp) {
        error_log($entity->id());
      }
    }
  }

  static function batchStart(&$context) {
    
  }

  static function batchFinished($success, $results, $operations) {
    \Drupal::messenger()->addMessage(t('Done.'));
  }

}
