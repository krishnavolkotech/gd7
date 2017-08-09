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
    $result['im_attachment_upload_form'] = \Drupal::formBuilder()->getForm(
      'Drupal\cust_group\Form\ImAttachmentsUploadForm');
    $result['im_attachment_upload_form']['#prefix'] = '<div class = "im-attachment-filesupload-form-wrapper">';
    $result['im_attachment_upload_form']['#suffix'] = '</div><div id="im-attachment-files-list">';
    
    $result['im_attachment_filter_element'] = \Drupal::formBuilder()->getForm(
      '\Drupal\cust_group\Form\ImAttachmentUploadFilterForm');
    $result['im_attachment_filter_element']['#prefix'] = '<div class = "im-attachment-filter-form-wrapper">';
    $result['im_attachment_filter_element']['#suffix'] = '</div>';
    
    $exposedFilterData = \Drupal::request()->query->all();
    $statename = isset($exposedFilterData['state']) && $exposedFilterData['state'] != 1 ? hzd_states()[$exposedFilterData['state']] : '';
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'im_upload_page', '=');
    if (!empty($statename)) {
      $nids->condition('field_state', $statename, '=');
    }
    $nodeids = $nids->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nodeids);
    foreach ($nodes as $key => $node) {
      if (!empty($node->get('field_im_upload_page_files')
        ->referencedEntities())
      ) {
        foreach ($node->get('field_im_upload_page_files')
                   ->referencedEntities() as $fileitem) {
          $files[] = $fileitem;
          $nodeData[$fileitem->id()] = $node;
        }
      }
    }
    if (empty($files)) {
      $result['empty_files'] = array(
        '#type' => 'markup',
        '#markup' => $this->t("No files uploaded yet."),
      );
      return $result;
    }
    $result['files'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['files']],
      '#suffix' => '</div>',
      '#header' => [
        $this->t('Land'),
        $this->t('Filename'),
        $this->t('Description'),
        $this->t('Ticket ID'),
        $this->t('Date uploaded'),
        $this->t('File size'),
        $this->t('User'),
        $this->t('Action')
      ],
    ];
    foreach ($files as $file) {
      //$file = reset($file);
      $attachment = \Drupal::entityTypeManager()
        ->getStorage('cust_group_imattachments_data')
        ->loadByProperties(['fid' => $file->id()]);
      $attachment = reset($attachment);
      $result['files'][$file->id()]['state'] = [
        '#markup' => $nodeData[$file->id()]->get('field_state')->value,
      ];
      $result['files'][$file->id()]['filename'] = [
        '#type' => 'link',
        '#title' => $file->getFileName(),
        '#url' => \Drupal\Core\Url::fromUri($file->url()),
      ];
      $description = $attachment ? $attachment->get('description')->value : '';
      $result['files'][$file->id()]['description'] = [
        '#markup' => $description,
      ];
      $ticketid = $attachment ? $attachment->get('ticket_id')->value : '';
      $result['files'][$file->id()]['ticketid'] = [
        '#markup' => $ticketid,
      ];
      $created = $attachment ? format_date($attachment->getCreatedTime(), 'medium', '', NULL, NULL) : '';
      $result['files'][$file->id()]['dateupload'] = [
        '#markup' => $created,
      ];
      $result['files'][$file->id()]['filesize'] = [
        '#markup' => format_size($file->getSize(), NULL),
      ];
      $result['files'][$file->id()]['owner'] = [
        '#type' => 'link',
        '#title' => $file->getOwner()->getDisplayName(),
        '#url' => $file->getOwner()->toUrl(),
      ];
      
      $result['files'][$file->id()]['delete'][$file->id()] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => \Drupal\Core\Url::fromRoute('cust_group.imattachment_delete_confirm', [
          'fid' => $file->id(),
          'nid' => $nodeData[$file->id()]->id()
        ]),
        '#attributes' => ['class' => ['btn btn-danger']]
      ];
    }
    
    
    return $result;
  }
  
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::access($account, $return_as_object);
    $routeMatch = Drupal::routeMatch();
    if (in_array($routeMatch->getRouteName(), [
      'entity.node.edit_form',
      'node.add'
    ])) {
      return AccessResult::forbidden();
    }
    return $access;
  }
  
}
