<?php

namespace Drupal\hzd_earlywarnings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cust_group\Controller\CustNodeController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Implements a form for the creation of early warnings.
 */
class CreateEarlyWarningForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hzd_earlywarnings_create_early_warning_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get Service and Release from query parameters.
    $service = \Drupal::request()->query->get('services');
    $rel = \Drupal::request()->query->get('releases');

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 128,
      '#required' => TRUE,
    ];
    // Gets the enabled services for this group.
    $services = getErlywarningServies();
    $form['field_service'] = [
      '#type' => 'select',
      '#title' => $this->t('Service'),
      '#options' => $services,
      '#empty_option' => '<Verfahren>',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::earlyWarningReleaseCallback',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'edit-field_release_ref',
        'progress' => [
          'type' => 'bar',
          'message' => $this->t('Loading Releases...'),
        ],
      ]
    ];
    if ($service) {
      // Preselect service, if present in query.
      $form['field_service']['#value'] = $service;
    }
    $form['field_release_ref'] = [
      '#type' => 'select',
      '#title' => $this->t('Release'),
      '#options' => ["0" => '<Release>'],
      '#required' => TRUE,
      '#prefix' => '<div id="edit-field_release_ref">',
      '#suffix' => '</div>',
      '#default_value' => "0",
      // '#value' => "0",
    ];

    if ($selectedValue = $form_state->getValue('field_service')) {
      // If a service got selected, ajax callback causes a rerender and the 
      // release options should be filled accordingly.
      // $selectedText = $form['field_service']['#options'][$selectedValue];
      $releases = get_earlywarning_release($selectedValue);
      $releases['releases'] = ["0" => '<Release>'] + $releases['releases'];
      $form['field_release_ref']['#options'] = $releases['releases'];
    }

    if ($rel) {
      // Get releases for the service in the query.
      $releases = get_earlywarning_release($service);
      $releases['releases'] = ["0" => '<Release>'] + $releases['releases'];
      $form['field_release_ref']['#options'] = $releases['releases'];
      // Preselect release, if present in query.
      $form['field_release_ref']['#value'] = $rel;
    }

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => 'Nachricht',
      '#format' => 'full_html',
      '#prefix' => '<div class="form-group field--type-text-with-summary">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];

    $current_user = \Drupal::service('current_user');
    $current_user_roles = $current_user->getRoles();
    if (array_intersect(['site_administrator','administrator'], $current_user_roles) || CustNodeController::isGroupAdmin(1) == TRUE) {
      // Add notification radio for site and group admin only.
      $form['notification'] = array(
        '#type' => 'fieldset',
        '#weight' => 99,
      );
      $form['notification']['node_notification_checkbox'] = send_notification_form_element();
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['#prefix'] = '<div class="node-form">';
    $form['#suffix'] = '</div>';

    // Neu: Service Reference.
    // $form['field_service']['widget'][0]['target_id']['#default_value'] = Node::load($service);
    // Neu: Release Reference.
    // $form['field_release_ref']['widget'][0]['target_id']['#default_value'] = Node::load($rel);
    if ($rel && $service) {
      // Disable service and release form elements, if query parameters are present.
      $form['field_service']['#attributes']['readonly'] = 'readonly';
      $form['field_release_ref']['#attributes']['readonly'] = 'readonly';
      $form['field_service']['#disabled'] = TRUE;
      $form['field_release_ref']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('field_release_ref') === "0") {
      // Default validation doesn't work, since "0" is selectable.
      $form_state->setErrorByName('field_release_ref', $this->t('Please select a release.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Creates the node.
    $node = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'early_warnings',
      'title' => $form_state->getValue('title'),
      'field_service' => $form_state->getValue('field_service'),
      'field_release_ref' => $form_state->getValue('field_release_ref'),
      'body' => $form_state->getValue('body'),
    ]);
    $node->save();
    $this->messenger()->addStatus($this->t('Early Warning gespeichert: @title', ['@title' => $form_state->getValue('title')]));
    $route = 'entity.node.canonical';
    $url = Url::fromRoute($route, ['node' => $node->id()])->toString();
    $response = new RedirectResponse($url);
    $response->send();
    // The success message.
  }

  /**
   * Ajax callback function.
   * 
   * Changes the release select options on service selection.
   */
public function earlyWarningReleaseCallback(array &$form, FormStateInterface $form_state) {
  // Check, if service field has a selected option.
  if ($selectedValue = $form_state->getValue('field_service')) {
    $selectedText = $form['field_service']['#options'][$selectedValue];
    // Get the releases for the selected service.
    $releases = get_earlywarning_release($selectedValue);
    $releases['releases'] = ["0" => '<Release>'] + $releases['releases'];
    $form['field_release_ref']['#options'] = $releases['releases'];
  }
  // Return the updated release form element.
  return $form['field_release_ref']; 
}

}
