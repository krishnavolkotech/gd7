<?php

namespace Drupal\cust_group\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'ImAttachmentsUploadedFilesBlock' block.
 *
 * @Block(
 *  id = "im_attachments_uploaded_files_block",
 *  admin_label = @Translation("Im attachments uploaded files block"),
 * )
 */
class ImAttachmentsUploadedFilesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    //$node = \Drupal\node\Entity\Node::load(896);
    $files = $node->get('field_im_upload_page_files')->referencedEntities();
    //kint($files);exit;
    if(empty($files)) {
        //return $buidl['#markup'] = $this->t('No files uploaded yet.');
        return array(
          '#type' => 'markup',
          '#markup' => $this->t("No files uploaded yet."),
        );
    }
    $build['files'] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['files']],
        '#header' => [$this->t('Filename'), $this->t('Description'), 
                      $this->t('Ticket ID'), $this->t('Date uploaded'),
                      $this->t('File size'), $this->t('User'),
                      $this->t('Action')],
    ];
    foreach ($files as $file) {
      $attachment = \Drupal::entityTypeManager()->getStorage('cust_group_imattachments_data')->loadByProperties(['fid' => $file->id()]);
      $attachment = reset($attachment);
      $build['files'][$file->id()]['filename'] = [
          '#type' => 'link',
          '#title' => $file->getFileName(),
          '#url' => \Drupal\Core\Url::fromUri($file->url()),
      ];
      $description = $attachment ? $attachment->get('description')->value : '';
      $build['files'][$file->id()]['description'] = [
          '#markup' => $description,
      ];
      $ticketid = $attachment ? $attachment->get('ticket_id')->value : '';
      $build['files'][$file->id()]['ticketid'] = [
          '#markup' => $ticketid,
      ];
      $created = $attachment ? format_date($attachment->getCreatedTime(), 'medium', '', NULL, NULL) : '';
      $build['files'][$file->id()]['dateupload'] = [
          '#markup' => $created,
      ]; 
      $build['files'][$file->id()]['filesize'] = [
          '#markup' => format_size($file->getSize(),NULL),
      ]; 
      $build['files'][$file->id()]['owner'] = [
          '#type' => 'link',
          '#title' => $file->getOwner()->getDisplayName(),
          '#url' => $file->getOwner()->toUrl(),
      ];

      $build['files'][$file->id()]['delete'][$file->id()] = [
          '#type' => 'link', 
          '#title' => $this->t('Delete'), 
          '#url' => \Drupal\Core\Url::fromRoute('cust_group.imattachment_delete_confirm', ['fid' => $file->id(), 'nid' => $node->id()]), 
          '#attributes' => ['class' => ['btn btn-danger']]
      ];
    }

    return $build;
  }

  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::access($account, $return_as_object);
    $routeMatch = Drupal::routeMatch();
    if (in_array($routeMatch->getRouteName(),['entity.node.edit_form','node.add'])) {
      return AccessResult::forbidden();
    }
    return $access;
  }
  public function getCacheTags() {
     if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }
}
