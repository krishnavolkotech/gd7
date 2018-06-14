<?php

namespace Drupal\hzd_risk_management\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'tokenized_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tokenized_field_formatter",
 *   label = @Translation("Tokenized field formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TokenizedFieldFormatter extends EntityReferenceFormatterBase{

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
	'token_data'=>""
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
	'token_data'=>['#type'=>'textfield','#title'=>t('Token to generate field markup'), '#default_value'=>$this->getSetting('token_data')]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $data = $this->getSetting('token_data');
    $token_service = \Drupal::token();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $item) {
      $elements[$delta] = ['#markup'=>$token_service->replace($data, array('node' => $item))];
    }
    return $elements;

  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
