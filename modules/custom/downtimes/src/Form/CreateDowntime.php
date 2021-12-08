<?php

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Class CreateDowntime.
 *
 * @package Drupal\downtimes\Form
 */
class CreateDowntime extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_downtime';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $date_format = 'd.m.Y H:i';

    $help_markup = t('Geplante Wartungs- und Blockzeiten, die sich auf länderübergreifende KONSENS-Verfahren auswirken, müssen - soweit sie an Arbeitstagen durchgeführt werden - dienstags und/oder mittwochs in der Zeit nach 18:00 Uhr durchgeführt werden
                        <em>(Beschluss AutomSt III/2011 TOP K9).</em>
                        <br>
                        <br>
                        <strong>Geplante Blockzeit:</strong>
                        Nichterreichbarkeit durch planmäßige Wartungsarbeiten
                        <br>
                        <strong>Land:</strong>
                        Das Land oder System, in dem die Wartungsarbeiten ausgeführt werden.
                        <br>
                        <strong>Verfahren/ZPS:</strong>
                        Verfahren oder ZPS, die durch die Wartungsarbeiten länderübergreifend nicht zur Verfügung stehen.
                        <br>
                        <br>
                        Blockzeitmeldungen im BpK werden als regulär also konform mit dem
                        <em>Beschluss AutomSt III/2011 TOP K9 </em>
                        gewertet, wenn Beginn und Ende in der Zeit von Dienstag und Mittwoch 18:00 Uhr bis jeweils 05:59 des Folgetages liegen.
                        <br>
                        Bis zum Abschluss gültiger SLAs wird für alle Verfahren eine Vorlaufzeit von 2 Tagen konfiguriert.
                        <br>
                        Ihre Meldung wird an alle Abonnenten der Benachrichtigungsoption "Störungen und Blockzeiten" versandt.
                        <br>
                        <br>
                        Bitte denken Sie unbedingt daran, Ihre Meldung zu gegebener Zeit wieder zu entfernen, um die Aktualität des BpK zu gewährleisten. Durch Beheben wird die Meldung in das Archiv verschoben.');
    $form['static_desc'] = [
      '#type' => 'item',
      '#markup' => $help_markup,
    ];
    $form['states'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('States'),
      '#description' => t('Wählen Sie das Land aus, in dem die Wartungsarbeiten ausgeführt werden. Mehrfachauswahl ist möglich.'),
      '#options' => HzdcustomisationStorage::get_published_services(),
    ];
    $form['services_effected'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Services Affected'),
      '#description' => t('Wählen Sie die Verfahren oder ZPS aus, die durch die Wartungsarbeiten länderübergreifend nicht zur Verfügung stehen. Mehrfachauswahl ist möglich.'),
      '#options' => HzdcustomisationStorage::get_published_services(),
    ];
    $form['start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#date_format' => $date_format,
      '#description' => date($date_format, time()),
      '#default_value' => time()
    ];
    $form['end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#date_format' => $date_format,
      '#description' => date($date_format, time()),
      '#default_value' => time()
    ];
    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason'),
    ];
    $form['reason_for_noncompliance'] = [
      '#type' => 'select',
      '#title' => $this->t('Reason for scheduling outside maintenance window'),
      '#description' => $this->t('Please select a reason'),
      '#options' => maintenance_reasons(),
      '#size' => 5,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . $value);
    }
  }

}
