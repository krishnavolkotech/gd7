<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;


/**
 *
 */
class ArchiveDeployedReleaseForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'archive_deployed_release_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state,Node $node = null) {
        $form['#attributes'] = ['class'=>['inline-form']];
        $form['deployed_release'] = [
            '#type'=>'hidden',
            '#value'=>$node->id(),
        ];
        $form['links'] = ['#type'=>'container'];
        $form['links']['edit_release'] = [
            '#type'=>'link',
            '#url'=>$node->toUrl('edit-form'),
            '#title'=>$this->t('Edit'),
            '#weight'=>1,
            '#attributes'=>['class'=>['btn-default button'],'style'=>'margin-right:5px;']
        ];
        $form['links']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Archive'),
            '#attributes'=>['onclick'=>'if(!confirm("dsadasda")){return false}'],
            '#weight'=>3,
        );
        return $form;
    }

    /**
     * {@inheritDoc}.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $nid = $form_state->getValue('deployed_release');
        $node = Node::load($nid);
        if($node){
            $node->set('field_archived_release',1)->save();
            \Drupal::service('cache_tags.invalidator')->invalidateTags(['hzd_release_management:releases']);
            if ($node->field_environment->value == 1) {
                // If environment is Production, delete cache for deployed releases overview table
                // $cids = ['deployedReleasesOverview459', 'deployedReleasesOverview460'];
                // \Drupal::cache()->deleteMultiple($cids);
                \Drupal::service('cache_tags.invalidator')->invalidateTags(['deployedReleasesOverview']);
            }
            $form_state->setRedirect('hzd_release_management.deployed_releases',['group'=>5]);
            drupal_set_message(t('Release Archived'));
        }
    }

}
