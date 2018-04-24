<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;


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
    public function buildForm(array $form, FormStateInterface $form_state,Node $node = null, $access = false, $group_id = 5) {
        $form['#attributes'] = ['class'=>['inline-form']];
        $form['deployed_release'] = [
            '#type'=>'hidden',
            '#value'=>$node->id(),
        ];
        $form['links'] = ['#type'=>'container'];
        if($access){
            $buildRedirUrl = Url::fromRoute('hzd_release_management.deployed_releases', ['group' => $group_id])->toString();
            $form['links']['edit_release'] = [
                '#type'=>'link',
                '#url'=>$node->toUrl('edit-form',[
                    'query'=>[
                        'ser' => $node->get('field_release_service')->value,
                        'rel' => $node->get('field_earlywarning_release')->value,
                        'env' => $node->get('field_environment')->value,
                        'destination' => $buildRedirUrl,
                        ]
                    ]
                ),
                '#title'=>$this->t('Edit'),
                '#weight'=>1,
                '#attributes'=>['class'=>['btn-default button'],'style'=>'margin-right:5px;']
            ];
        }
        // pr($node->get('field_archived_release')->value);exit;
        if($node->get('field_archived_release')->value != 1){
            $form['links']['submit'] = array(
                '#type' => 'submit',
                '#value' => t('Archive'),
                '#attributes'=>['onclick'=>'if(!confirm("Möchten Sie wirklich archivieren?")){return false}'],
                '#weight'=>3,
            );
        }
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
            $form_state->setRedirect('hzd_release_management.deployed_releases',['group'=>$group_id]);
            drupal_set_message(t('Release Archived'));
        }
    }

}
