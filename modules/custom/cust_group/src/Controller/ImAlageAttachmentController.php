<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;

class ImAlageAttachmentController extends ControllerBase {
    
    
    public function defaultStatesInsert() {
      $states = \Drupal::database()->select('states', 'st')
              ->fields('st', ['state', 'abbr'])
              ->execute()
              ->fetchAll();
      foreach ($states as $key => $value) {
            $url = '/incident-management/ablage-attachments/' . strtolower($value->abbr);
            $path = \Drupal::service('path.alias_manager')->getPathByAlias($url);
            if(preg_match('/node\/(\d+)/', $path, $matches)) {
              $node = \Drupal\node\Entity\Node::load($matches[1]);
              if($node && empty($node->get('field_state')->value)) {
                $node->field_state->value = $value->state;
                $node->save();
              }
            }
      }
      return ['#markup' => $this->t('Successfully updated nodes with states')];
    }
}

