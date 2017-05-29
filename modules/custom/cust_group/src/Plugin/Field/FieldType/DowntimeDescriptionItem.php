<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\cust_group\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * Variant of the 'link' field that links to the current company.
 *
 * @FieldType(
 *   id = "downtime_description",
 *   label = @Translation("Downtime Description"),
 *   description = @Translation("Downtime Description."),
 *   default_widget = "string_textarea",
 *   default_formatter = "basic_string"
 * )
 */
class DowntimeDescriptionItem extends StringLongItem{
  /**
   * Whether or not the value has been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }
  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        // Some custom code that retrieves the current company.
        $desc = \Drupal::database()->select('downtimes','d')->fields('d',['description'])->condition('downtime_id',$entity->id())->execute()->fetchField();
        $this->setValue($desc);
      }
      $this->isCalculated = TRUE;
    }
  }

}
