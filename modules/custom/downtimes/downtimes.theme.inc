<?php

/**
 * @file
 * Defines theme functions for the Search API module.
 */
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Returns HTML for a fields form table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @return string
 *   The rendered HTML for a fields form table.
 *
 * @ingroup themeable
 */
/*
 * Custom service checkbox theme function
 */
function template_preprocess_service_checkbox($element) {
  //_form_set_attributes($element, array('form-checkbox'));
  $checkbox = '<input ';
  $checkbox .= 'type="checkbox" ';
  $checkbox .= 'name="' . $element['#name'] . '" ';
  $checkbox .= 'id="' . $element['#id'] . '" ';
  $checkbox .= 'value="' . $element['#return_value'] . '" ';
  $checkbox .= $element['#value'] ? ' checked="checked" ' : ' ';
  $checkbox .= ' />';

  $service_names = explode("|", $element['#title']);
  if (!is_null($element['#title'])) {
    $checkbox .= '<div class = "published-services"><label class="option" for="' . $element['#id'] . '"> ' . $service_names[0];

    if (isset($service_names[1])) {
        $checkbox .= $service_names[1] ;
    }
    $checkbox .= '</label></div>';
    
  }

  unset($element['#title']);

  return '<div class="form-checkbox ' . $element['#id'] . '-wrapper">' . $checkbox . '</div>';
}

/*
 * Custom checkboxes theme function
 */

function template_preprocess_service_checkboxes(&$variables) {
  /* Uncomment the below line to dump array values */
  $html = "";
  $element = $variables['element'];
  foreach ($element['#options'] as $key => $val) {
    /* Use our custom checkbox theme function */
    $html .= template_preprocess_service_checkbox($element[$key], $key);
  }
  $variables['html_element'] = $html;
}
