<?php
/**
 * Created by PhpStorm.
 * User: sudishth
 * Date: 6/8/16
 * Time: 2:32 PM
 */
namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an QuickinfoGroupID form.
 */
class QuickinfoGroupID extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'quickinfo_group_id';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['hzd_customizations.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('hzd_customizations.settings');

        $form['quickinfo_group_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Autoren RZ-Schnellinfo Group Id'),
            '#default_value' =>  $config->get('quickinfo_group_id') ?: NULL,
            '#prefix' => t('Please Enter Autoren RZ-Schnellinfo nid'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $this->config('hzd_customizations.settings')
            ->set('quickinfo_group_id', $form_state->getValue('quickinfo_group_id'))
            ->save();
    }

}
