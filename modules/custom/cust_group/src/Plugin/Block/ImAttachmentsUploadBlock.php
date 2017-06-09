<?php

namespace Drupal\cust_group\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'ImAttachmentsUploadBlock' block.
 *
 * @Block(
 *  id = "im_attachments_upload_block",
 *  admin_label = @Translation("Im attachments upload block"),
 * )
 */
class ImAttachmentsUploadBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = Drupal::routeMatch()->getParameter('node');

    $form = Drupal::formBuilder()->getForm('Drupal\cust_group\Form\ImAttachmentsUploadForm');

    return $form;
  }

  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::access($account, $return_as_object);
    $routeMatch = Drupal::routeMatch();
    if (in_array($routeMatch->getRouteName(),['entity.node.edit_form','node.add'])) {
      return AccessResult::forbidden();
    }
    return $access;
  }

}
