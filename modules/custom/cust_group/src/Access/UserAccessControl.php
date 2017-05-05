<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 4/5/17
 * Time: 9:36 PM
 */

namespace Drupal\cust_group\Access;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAccessControlHandler;

class UserAccessControl extends UserAccessControlHandler {
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
//    echo 12123;exit;
    $return = parent::checkFieldAccess($operation, $field_definition, $account);
    if ($operation == 'view' && $field_definition->getName() == 'mail') {
      return AccessResult::allowed();
    }
    return $return;
  }
}