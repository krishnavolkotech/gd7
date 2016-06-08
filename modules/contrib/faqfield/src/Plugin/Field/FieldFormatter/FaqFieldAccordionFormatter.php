<?php

/**
 * @file
 * Contains \Drupal\faqfield\Plugin\field\formatter\FaqFieldAccordionFormatter.
 */

namespace Drupal\faqfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'faqfield_accordion' formatter.
 *
 * @FieldFormatter(
 *   id = "faqfield_accordion",
 *   label = @Translation("jQuery Accordion"),
 *   field_types = {
 *     "faqfield"
 *   }
 * )
 */
class FaqFieldAccordionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'active' => 0,
      'heightStyle' => 'auto',
      'collapsible' => FALSE,
      'event' => 'click',
      'animate' => array(
        'easing' => 'linear',
        'duration' => 200,
      ),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Number of first active element.
    $elements['active'] = array(
      '#type' => 'number',
      '#title' => t('Default active'),
      '#placeholder' => t('None'),
      '#default_value' => $this->getSetting('active'),
      '#description' => t('Index of the active question starting from 0. If left empty and <em>Fully collapsible</em> is on, no question will be opened by default.'),
      '#maxlength' => 3,
      '#size' => 5,
    );
    // Whether auto heigth is enabled.
    $elements['heightStyle'] = array(
      '#type' => 'select',
      '#title' => t('Height style'),
      '#default_value' => $this->getSetting('heightStyle'),
      '#options' => array(
        'auto' => t('Auto') . ': ' . t('All panels will be set to the height of the tallest question.'),
        'fill' => t('Fill') . ': ' . t('Expand to the available height based on the accordions question height.'),
        'content' => t('Content') . ': ' . t('Each panel will be only as tall as its question.'),
      ),
      '#description' => t('Controls the height of the accordion and each panel.'),
    );
    // Whether elements are collabsible.
    $elements['collapsible'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fully collapsible'),
      '#default_value' => $this->getSetting('collapsible'),
      '#description' => t('Whether all the questions can be closed at once. Allows collapsing the active section.'),
    );
    // Name of triggering event.
    $elements['event'] = array(
      '#type' => 'textfield',
      '#title' => t('Event'),
      '#placeholder' => 'click',
      '#default_value' => $this->getSetting('event'),
      '#description' => t('The event on which to open a question. Multiple events can be specified, separated by a space.'),
    );

    // Animation options for the accordion formatter.
    $elements['animate'] = array(
      '#type' => 'details',
      '#title' => $this->t('Animation settings'),
      '#collapsed' => TRUE,
    );
    // Animation duration in milliseconds with the selected easing.
    $elements['animate']['duration'] = array(
      '#type' => 'number',
      '#title' => $this->t('Duration'),
      '#default_value' => $this->getSetting('animate')['duration'],
      '#description' => $this->t('Animation duration in milliseconds with the selected easing.'),
      '#min' => 0,
    );
    // Name of easing to use when the event is triggered.
    $elements['animate']['easing'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Easing'),
      '#placeholder' => 'linear',
      '#default_value' => $this->getSetting('animate')['easing'],
      '#description' => $this->t('Name of <a href="@link">easing</a> to use when the event is triggered.', array('@link' => 'http://api.jqueryui.com/easings/')),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    if (is_numeric($this->getSetting('active'))) {
      $active = $this->getSetting('active');
    }
    else {
      $active = t('None');
    }
    $summary[] = t('Default active: @element', array('@element' => $active));

    $height_style = '';
    switch ($this->getSetting('heightStyle')) {
      case 'auto':
        $height_style = t('Auto');
        break;
      case 'fill':
        $height_style = t('Fill');
        break;
      case 'content':
        $height_style = t('Content');
        break;
    }
    $summary[] = t('Height style') . ': ' . $height_style;

    if ($this->getSetting('collapsible')) {
      $summary[] = t('Fully collapsible');
    }

    $summary[] = t('Event: @event', array('@event' => $this->getSetting('event')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This will not be themeable, because changes would break jQuery UI accordion functionality!
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $default_format = $this->getFieldSetting('default_format');
    $settings = $this->getSettings();

    // Generate faqfield id by fieldname and entity id.
    $faqfield_id = 'faqfield_' . $this->fieldDefinition->getName() . '_' . $items->getEntity()->getEntityTypeId() . '_' . $items->getEntity()->id();

    // If active setting was left blank, we set FALSE so no element will be active.
    if (!is_numeric($settings['active'])) {
      $settings['active'] = FALSE;
    }

    $element_items = array();
    foreach ($items as $item) {
      // Decide whether to use the default format or the custom one.
      $format = (!empty($item->answer_format) ? $item->answer_format : $default_format);

      $element_items[] = array(
        'question' => $item->question,
        'answer' => $item->answer,
        'answer_format' => $format,
      );
    }

    $elements[0] = array(
      '#theme' => 'faqfield_jquery_accordion_formatter',
      '#items' => $element_items,
      '#id' => $faqfield_id,
      '#attached' =>  array(
        // Add FAQ Field accordion library.
        'library' => array(
          'faqfield/faqfield.accordion',
        ),
        'drupalSettings' =>  array(
          'faqfield' =>  array(
            '#' . $faqfield_id => $settings
          ),
        ),
      ),
    );
    return $elements;
  }

}
