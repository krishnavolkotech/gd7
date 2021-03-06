<?php

namespace Drupal\cust_group\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Query\Condition;


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
	$string = trim($string);

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
   	    $or = new Condition('OR');
            $or->condition('fm.filename',"%" . $string . "%", 'LIKE')
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
            if($file_entity && $file_entity->getOwner()) {
              $files[] = $file_entity;
              $nodeData[$value->field_im_upload_page_files_target_id] = $value->entity_id;
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
        }
        else if ($incidentManagementGroupMember && group_request_status($incidentManagementGroupMember)) {
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
                $field_state = node_get_field_data_fast([$nodeData[$file->id()]], 'field_state')[$nodeData[$file->id()]];
                $state = hzd_states_abbr()[$field_state];

                $filenameuri = \Drupal\Core\Url::fromUri($file->createFileUrl(FALSE));
                $filename = \Drupal::service('link_generator')
                    ->generate($file->getFileName(), $filenameuri);

                $description = $attachment ? $attachment->get('description')->value : '';

                $ticketid_value = $attachment ? $attachment->get('ticket_id')->value : '';

                $ticketid_form = \Drupal::formBuilder()->getForm('Drupal\cust_group\Form\Imupdateticket', ['file_id' => $file->id(), 'ticket' => $ticketid_value]);
                $ticketid = ['ticket_id' => ['#markup' => "<div class='show-ticket-value'>". $ticketid_value . "</div>"],
                             'update_form' => $ticketid_form
                ];
  
                 $date_formatter = \Drupal::service('date.formatter');
                $created = $attachment ? $date_formatter->format($attachment->getCreatedTime(), 'medium', '', NULL, NULL) : '';

                $filesize = format_size($file->getSize(), NULL);

              $owner = '-';
                if($file->getOwner()) {
                  $owner = \Drupal::service('link_generator')
                    ->generate($file->getOwner()->getDisplayName(), $file->getOwner()->toUrl());
                }
                $deletelink = \Drupal\Core\Url::fromRoute('cust_group.imattachment_delete_confirm', [
                    'fid' => $file->id(),
                    'nid' => $nodeData[$file->id()]
                ], ['attributes' => ['class' => 'btn btn-danger'], 'query' => $exposedFilterData]);
                
                if ($showDelete || $field_state == $userstateid) {
                    $delete = \Drupal::service('link_generator')->generate(t('Delete'), $deletelink);
                    $edit = \Drupal\Core\Link::fromTextAndUrl(t('Edit'), \Drupal\Core\Url::fromUserInput('#', ['attributes' => ['class' => ['edit-ticket-id', 'btn' ,'btn-default']], 'fragment' => '#']));
                    //$edit = ['#markup' => "<a class='edit-ticket-id btn btn-default' href='#'>". $this->t('Edit')."</a>"];
                } else {
                    $edit = '';
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
                    array('data' => $edit, 'class' => 'edit-cell'),
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
                ['data' => $this->t('Action'),
                'colspan' => 2]
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
