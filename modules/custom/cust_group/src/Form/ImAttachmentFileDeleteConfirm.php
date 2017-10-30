<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ImAttachmentFileDeleteConfirm extends ConfirmFormBase {

    /**
     * The ID's of the item to delete.
     * @var string
     */
    protected $fid;
    protected $nid;

    /**
     * {@inheritdoc}.
     */
    public function getFormId() {
        return 'im_attachment_file_delete_confirm';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return t('Do you want to delete?');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        return new Url('entity.node.canonical', ['node' => 826]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('Delete');
    }


    /**
     * {@inheritdoc}
     */
    public function getCancelText() {
        return $this->t('Cancel');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $fid = NULL, $nid = NULL) {
        $this->fileid = $fid;
        $this->nodeid = $nid;
        $form['fid'] = [
            '#type' => 'hidden',
            '#value' => $fid,
        ];
        $form['nid'] = [
            '#type' => 'hidden',
            '#value' => $nid,
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();
        $nid = $values['nid'];
        $fid = $values['fid'];
        $node = \Drupal\node\Entity\Node::load($nid);
        $count = 0;
        foreach ($node->get('field_im_upload_page_files')->getValue() as $file_field) {
          if ($file_field['target_id'] == $fid) {
            $node->get('field_im_upload_page_files')->removeItem($count);
            file_delete($file_field['target_id']);
            $entity = \Drupal::entityTypeManager()->getStorage('cust_group_imattachments_data')->loadByProperties(['fid' => $fid]);
            $entity = reset($entity);
            if($entity) {
             $entity->delete();
            }
          } else {
            $count++;
          }
        }
        $node->save();
        $form_state->setRedirect('entity.node.canonical', ['node' => 826]);
        drupal_set_message('File was successfully deleted!');
    }
}
