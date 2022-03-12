<?php

declare(strict_types = 1);

namespace Drupal\views_merge_rows\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Provides interface to manage merge options on a per-field basis.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *     id = "views_merge_rows",
 *     title = @Translation("Merge rows"),
 *     help = @Translation("Merges rows with the same values in the specified fields."),
 *     no_ui = FALSE
 * )
 */
class ViewsMergeRowsDisplayExtenderPlugin extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['merge_rows'] = ['default' => FALSE];
    $options['field_config'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('section') != 'views_merge_rows') {
      return;
    }

    $form['#tree'] = TRUE;
    $form['#theme'] = 'merge_rows_theme';
    $form['#title'] .= $this->t('Merge rows with the same content.');

    $form['warning_markup'] = [];
    if ($this->displayHandler->usesPager()) {
      $form['warning_markup'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('It is highly recommended to disable pager if you merge rows.'),
          ],
        ],
      ];
    }

    $form['merge_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Merge rows with the same content in the specified fields'),
      '#default_value' => $this->options['merge_rows'],
    ];

    // Create an array of allowed columns from the data we know:
    $field_names = $this->displayHandler->getFieldLabels();
    foreach ($field_names as $field => $name) {
      // Markup for the field name.
      $form['field_config'][$field]['name'] = ['#markup' => $name];
      // Select for merge options.
      $form['field_config'][$field]['merge_option'] = [
        '#type' => 'select',
        '#options' => [
          'filter' => $this->t('Use values of this field as a filter'),
          'merge' => $this->t('Merge values of this field'),
          'merge_unique' => $this->t('Merge unique values of this field'),
          'first_value' => $this->t('Use the first value of this field'),
          'highest_value' => $this->t('Use the highest value of this field'),
          'lowest_value' => $this->t('Use the lowest value of this field'),
          'average' => $this->t('Use the average value of this field'),
          'std_deviation' => $this->t('Use the sample standard deviation of this field'),
          'sum' => $this->t('Sum values of this field'),
          'count' => $this->t('Count merged values of this field'),
          'count_unique' => $this->t('Count merged unique values of this field'),
          'count_minus_count_unique' => $this->t('Calculate the number of merged values minus the number of merged unique values of this field'),
        ],
        '#default_value' => $this->options['field_config'][$field]['merge_option'] ?? 'merge_unique',
      ];

      $form['field_config'][$field]['exclude_first'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Exclude first option for @field_name', [
          '@field_name' => $name,
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $this->options['field_config'][$field]['exclude_first'] ?? FALSE,
      ];

      $form['field_config'][$field]['prefix'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Prefix for @field_name', [
          '@field_name' => $name,
        ]),
        '#title_display' => 'invisible',
        '#size' => (int) 10,
        '#default_value' => $this->options['field_config'][$field]['prefix'] ?? '',
      ];

      $form['field_config'][$field]['separator'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Separator for @field_name', [
          '@field_name' => $name,
        ]),
        '#title_display' => 'invisible',
        '#size' => (int) 10,
        '#default_value' => $this->options['field_config'][$field]['separator'] ?? ', ',
      ];

      $form['field_config'][$field]['suffix'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Suffix for @field_name', [
          '@field_name' => $name,
        ]),
        '#title_display' => 'invisible',
        '#size' => (int) 10,
        '#default_value' => $this->options['field_config'][$field]['suffix'] ?? '',
      ];
    }

    $form['merge_options_description_title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Merge options details'),
    ];
    $form['merge_options_description'] = [
      '#theme' => 'table',
      '#header' => [
        $this->t('Option'),
        $this->t('Description'),
      ],
      '#rows' => [
        [
          $this->t('Use values of this field as a filter'),
          $this->t('Checks which rows should be merged. If several rows contain exactly the same values in all of these fields, they are merged together.'),
        ],
        [
          $this->t('Merge values of this field'),
          $this->t('All the values appears in the resulting row.'),
        ],
        [
          $this->t('Merge unique values of this field'),
          $this->t('The resulting row will contain unique values from the merged rows.'),
        ],
        [
          $this->t('Use the first value of this field'),
          $this->t('Only the value from the first merged rows is used. The values in other rows are disregarded.'),
        ],
        [
          $this->t('Use the highest value of this field'),
          $this->t('The resulting row will contain the highest numerical value from the merged rows.'),
        ],
        [
          $this->t('Use the lowest value of this field'),
          $this->t('The resulting row will contain the highest numerical value from the merged rows.'),
        ],
        [
          $this->t('Use the average value of this field'),
          $this->t('The resulting row will contain the average value from the merged rows.'),
        ],
        [
          $this->t('Use the sample standard deviation of this field'),
          $this->t('The resulting row will contain the sample standard deviation value from the merged rows.'),
        ],
        [
          $this->t('Sum values of this field'),
          $this->t('The resulting row will contain the sum of values from the merged rows.'),
        ],
        [
          $this->t('Count merged values of this field'),
          $this->t('The resulting row will contain the counter of the merged rows.'),
        ],
        [
          $this->t('Count merged unique values of this field'),
          $this->t('The resulting row will contain the counter of the merged unique values.'),
        ],
        [
          $this->t('Calculate the number of merged values minus the number of merged unique values of this field'),
          $this->t('The resulting row will contain the difference between counter of the merged values and the counter of the merged unique values.'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('section') != 'views_merge_rows') {
      return;
    }
    /** @var array $form_state_options */
    $form_state_options = $form_state->getValue('options');
    foreach ($form_state_options as $option => $value) {
      $this->options[$option] = $value;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function optionsSummary(&$categories, &$options): void {
    if (!$this->displayHandler->usesFields()) {
      return;
    }

    $options['views_merge_rows'] = [
      'category' => 'other',
      'title' => $this->t('Merge rows'),
      'value' => $this->options['merge_rows'] ? $this->t('Yes') : $this->t('No'),
      'desc' => $this->t('Allow merging rows with the same content in the specified fields.'),
    ];
  }

  /**
   * Returns configuration for row merging.
   *
   * Only returns the configuration for the fields present in the view. In case
   * configuration still has some entries about removed fields.
   *
   * If a new field was added to the view, the default configuration for this
   * field is returned.
   *
   * Impossible to provide default field values for fields in ::defineOptions()
   * because displayHandler property is not available at this moment.
   *
   * @return array
   *   Configuration for row merging.
   */
  public function getOptions(): array {
    $options = [
      'merge_rows' => FALSE,
      'field_config' => [],
    ];

    if ($this->displayHandler->usesFields()) {
      $options['merge_rows'] = $this->options['merge_rows'];

      $field_default_options = [
        'merge_option' => 'merge_unique',
        'exclude_first' => FALSE,
        'prefix' => '',
        'separator' => ', ',
        'suffix' => '',
      ];

      $fields = $this->displayHandler->getOption('fields');
      foreach (\array_keys($fields) as $field) {
        $options['field_config'][$field] = $field_default_options;

        if (isset($this->options['field_config'][$field])) {
          $options['field_config'][$field] = $this->options['field_config'][$field] + $field_default_options;
        }
      }
    }

    return $options;
  }

}
