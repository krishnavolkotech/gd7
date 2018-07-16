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
        $statename = isset($exposedFilterData['state']) && $exposedFilterData['state'] != 1 ? $exposedFilterData['state'] : '';
        $string = isset($exposedFilterData['string']) && $exposedFilterData['string'] != '' ? $exposedFilterData['string'] : '';

        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->join('node__field_im_upload_page_files', 'imuf', 'n.nid = imuf.entity_id');
        $query->join('node__field_state', 'fs', 'fs.entity_id = imuf.entity_id');
        $query->leftJoin('im_attachments_data', 'imd', 'imd.fid = imuf.field_im_upload_page_files_target_id');
        $query->leftJoin('file_managed', 'fm', 'imd.fid = fm.fid');
        $query->condition('imuf.bundle', 'im_upload_page', '=');
        if (!empty($statename)) {
            $query->condition('fs.field_state_value', $statename, '=');
        }
        if (!empty($string)) {
            $or = db_or()->condition('fm.filename',"%" . $string . "%", 'LIKE')
                         ->condition('imd.description__value',"%" . $string . "%", 'LIKE')
                         ->condition('imd.ticket_id',"%" . $string . "%", 'LIKE');
            $query->condition($or);
        }

        $count_query = clone $query;
        $query->orderBy('imd.created','desc');
        $count_query->addExpression('Count(n.nid)');
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
        $pager->setCountQuery($count_query);
        $pager->limit(20);
        
        $pager->fields('imuf', ['entity_id', 'field_im_upload_page_files_target_id']);
        $pager->fields('imd', ['created']);
        $pager->distinct();
        $fileids = $pager->execute()->fetchAll();
        $files = [];
        foreach ($fileids as $key => $value) {
            $file_entity = \Drupal\file\Entity\File::load($value->field_im_upload_page_files_target_id);
            if($file_entity && $file_entity->getEntityTypeId() == 'file') {
              $files[] = $file_entity;
              $nodeData[$value->field_im_upload_page_files_target_id] = \Drupal\node\Entity\Node::load($value->entity_id);
            }
        }

//        if (empty($files)) {
//            $result['empty_files'] = array(
//                '#type' => 'markup',
//                '#markup' => $this->t("No files uploaded yet."),
//            );
//            return $result;
//        }
        $rows = [];
        $currentUser = \Drupal::currentUser();
        $incidentManagement = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
        $incidentManagementGroupMember = $incidentManagement->getMember($currentUser);
        if (in_array('site_administrator', $currentUser->getRoles()) || $currentUser->id() == 1) {
          $showDelete = TRUE;
        } else if ($incidentManagementGroupMember && $incidentManagementGroupMember->getGroupContent()
                    ->get('request_status')->value == 1) {
            $roles = $incidentManagementGroupMember->getRoles();
            if (in_array($incidentManagement->getGroupType()->id() . '-admin', array_keys($roles))) {
              $showDelete = TRUE;
            } else {
              $showDelete = FALSE;
            }
               
        }
        $userstateid = \Drupal::database()->select('cust_profile', 'cp')
            ->fields('cp', array('state_id'))
            ->condition('cp.uid', $currentUser->id())
            ->execute()->fetchField();

        foreach ($files as $file) {
            if ($file) {
                $attachment = \Drupal::entityTypeManager()
                    ->getStorage('cust_group_imattachments_data')
                    ->loadByProperties(['fid' => $file->id()]);
                $attachment = reset($attachment);
                $state = hzd_states_abbr()[$nodeData[$file->id()]->get('field_state')->value];

                $filenameuri = \Drupal\Core\Url::fromUri($file->url());
                $filename = \Drupal::service('link_generator')
                    ->generate($file->getFileName(), $filenameuri);

                $description = $attachment ? $attachment->get('description')->value : '';

                $ticketid = $attachment ? $attachment->get('ticket_id')->value : '';

                $created = $attachment ? format_date($attachment->getCreatedTime(), 'medium', '', NULL, NULL) : '';

                $filesize = format_size($file->getSize(), NULL);

                $owner = \Drupal::service('link_generator')
                    ->generate($file->getOwner()->getDisplayName(), $file->getOwner()->toUrl());

                $deletelink = \Drupal\Core\Url::fromRoute('cust_group.imattachment_delete_confirm', [
                    'fid' => $file->id(),
                    'nid' => $nodeData[$file->id()]->id()
                ], ['attributes' => ['class' => 'btn btn-danger'], 'query' => $exposedFilterData]);
                if ($showDelete || $nodeData[$file->id()]->get('field_state')->value == $userstateid) {
                    $delete = \Drupal::service('link_generator')
                        ->generate(t('Delete'), $deletelink);
                } else {
                    $delete = '--';
                }

                $row = array(
                    array('data' => $state, 'class' => 'state-cell'),
                    array('data' => $filename, 'class' => 'filename-cell'),
                    array('data' => $description, 'class' => 'description-cell'),
                    array('data' => $ticketid, 'class' => 'ticketid-cell'),
                    array('data' => $created, 'class' => 'created-cell'),
                    array('data' => $filesize, 'class' => 'filesize-cell'),
                    array('data' => $owner, 'class' => 'owner-cell'),
                    array('data' => $delete, 'class' => 'delete-cell'),
                );

                $rows[] = $row;
            }
        }
        $result['files'] = [
            '#type' => 'table',
            '#attributes' => ['class' => ['files']],
            '#suffix' => '</div>',
            '#rows' => $rows,
            '#empty' => t('No files available.'),
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
        $result['pager'] = array(
            '#type' => 'pager',
            '#quantity' => 5,
            '#prefix' => '<div id="pagination">',
            '#suffix' => '</div>',
        );
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
