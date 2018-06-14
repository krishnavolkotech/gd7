<?php

namespace Drupal\hzd_risk_management\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * List of possible wrapper types for the table
 */
//const WRAPPERSLIST = ['container', 'fieldset', 'details'];

/**
 * Plugin implementation of the 'table' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "table_field",
 *   label = @Translation("Table (Fields as rows)"),
 *   description = @Translation("This fieldgroup renders the inner content in a
 *   simple table."), supported_contexts = {
 *     "view",
 *   }
 * )
 */
class Table extends FieldGroupFormatterBase {

  public static $wrapperList = ['container', 'fieldset', 'details'];
  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
        'fieldtitle' => 1,
        'wrapper' => key(self::$wrapperList),
        'required_fields' => $context == 'form',
      ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['fieldtitle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display field title in header.'),
      '#default_value' => $this->getSetting('fieldtitle'),
    ];

    $form['wrapper'] = [
      '#type' => 'select',
      '#options' => self::$wrapperList,
      '#title' => $this->t('Wrapper'),
      '#default_value' => $this->getSetting('wrapper'),
    ];

    $form['caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption'),
      '#default_value' => $this->getSetting('caption'),
    ];

    if ($this->context == 'form') {
      $form['required_fields'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Mark group as required if it contains required fields.'),
        '#default_value' => $this->getSetting('required_fields'),
        '#weight' => 2,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('fieldtitle')) {
      $summary[] = $this->t('Fields title will be displayed in table header');
    }
    else {
      $summary[] = $this->t('Fields title will NOT be displayed');
    }

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $table = [];

    if (is_string(self::$wrapperList[$this->getSetting('wrapper')])) {
      $element['#type'] = self::$wrapperList[$this->getSetting('wrapper')];
    }
//    $element['#attached']['library'][] = 'table_field_group/tablefieldgroup';
    $element['#title'] = $this->group->label;

    $table += [
      '#type' => 'table',
      '#title' => Html::escape($this->t($this->getLabel())),
    ];

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    $classes[] = 'table-field-group';
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    $table['#empty'] = t('There is nothing to display');

    if ($this->getSetting('caption') !== "") {
      $table['#caption'] = $this->getSetting('caption');
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.details';
      $element['#attached']['library'][] = 'field_group/core';
    }

    $fields = Element::children($element);
    $rows = [];
    $header = [];
    foreach ($fields as $key) {
      $row[0] = ['data'=>['#markup'=>$element[$key]['#title']],'class'=>['risk-labels']];
      $element[$key]['#label_display'] = 'hidden';
      $row[1] = render($element[$key]);
      unset($element[$key]);
      $rows[] = $row;
    }
    if ($this->getSetting('fieldtitle') == 1) {
      $table['#header'] = $header;
    }
    $table['#rows'] = $rows;

    $element['fields_in_table'] = $table;
  }


  /**
   * Return current group ID.
   *
   * @return string
   *   Current group ID.
   */
  protected function getGroupId() {
    if ($this->getSetting('id')) {
      return $this->getSetting('id');
    }
    return Html::getId('table_field_' . $this->group->group_name);
  }

}
