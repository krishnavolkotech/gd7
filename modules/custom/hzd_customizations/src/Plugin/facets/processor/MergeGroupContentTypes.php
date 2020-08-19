<?php

namespace Drupal\hzd_customizations\Plugin\facets\processor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MergeGroupContentTypes what allow merging facet content types.
 *
 * @package Drupal\hzd_customizations\Plugin\facets\processor
 *
 * @FacetsProcessor(
 *   id = "hzd_customizations_search_merge_group_content_types",
 *   label = @Translation("Display Selected group Content types."),
 *   description = @Translation("Display the Selected Group Content Type Facets."),
 *   stages = {
 *     "build" = 80
 *   }
 * )
 */
class MergeGroupContentTypes extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Extract all available group types then mapped then as valid options.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupTypes(): array {
    /** @var array $groupTypes */
    $groupTypes = array_map(function ($groupType) {
      /** @var \Drupal\node\Entity\GroupType $nodeType */
      return $groupType->label();
    }, $this->entityTypeManager->getStorage('node_type')->loadMultiple());
    return $groupTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    /** @var array $config */
    $config = $this->getConfiguration()['facet_groups'];

    // Gather the number of groups in the form already.
    $groups = $form_state->get('groups');

    // We have to ensure that there is at least one group.
    if (is_null($groups)) {
      $groups = count($config);
      $form_state->set('groups', $groups);
    }

    // Prepare form widget.
    $build['#tree'] = TRUE;
    $build['container_open']['#markup'] = '<div id="facet-group-fieldset-wrapper">';

    // Iterate same times as groups available.
    for ($i = 0; $i < $groups; $i++) {

      // Build details wrapper on each group.
      $build['facet_groups'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Facet group'),
        '#open' => FALSE,
      ];

      // Include field to overwrite facet name.
      $build['facet_groups'][$i]['facet_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Facet name'),
        '#default_value' => $config[$i]['facet_name'] ?? NULL,
      ];

      // Expose all possible content types available.
      $build['facet_groups'][$i]['parent_group'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Content types to be grouped.'),
        '#options' => $this->getGroupTypes(),
        '#default_value' => $config[$i]['parent_group'] ?? [],
      ];
    }

    // Close container element.
    $build['container_close']['#markup'] = '</div>';

    // Setup $.ajax buttons.
    $build['actions'] = [
      '#type' => 'actions',
    ];
    $build['actions']['add_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => [
        [$this, 'addOne'],
      ],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'facet-group-fieldset-wrapper',
      ],
    ];

    // If there is more than one group, add the remove button.
    if ($groups > 1) {
      $build['actions']['remove_group'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => [
          [$this, 'removeOne'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addMoreCallback'],
          'wrapper' => 'facet-group-fieldset-wrapper',
        ],
      ];
    }

    return $build;
  }

  /**
   * Submit handler for the "Add one more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('groups');
    $add_button = $groups + 1;
    $form_state->set('groups', $add_button);

    // Since our buildForm() method relies on the value of 'groups' to
    // generate 'facet_groups' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeOne(array &$form, FormStateInterface $form_state) {
    $groups = $form_state->get('groups');
    if ($groups > 1) {
      $remove_button = $groups - 1;
      $form_state->set('groups', $remove_button);
    }

    // Since our buildForm() method relies on the value of 'groups' to
    // generate 'facet_groups' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    /** @var array $facet_groups */
    $facet_groups = NestedArray::getValue($form, [
      'facet_settings',
      'hzd_customizations_search_merge_group_types',
      'settings',
      'facet_groups',
    ]);

    // Recreate container wrapper.
    $facet_groups['#prefix'] = '<div id="facet-group-fieldset-wrapper">';
    $facet_groups['#suffix'] = '</div>';

    return $facet_groups;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form_state->unsetValue('actions');
    parent::submitConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'facet_groups' => [
        [
          'facet_name' => '',
          'parent_group' => [],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var array $facet_groups */
    $facet_groups = $this->getConfiguration()['facet_groups'];

    /** @var \Drupal\facets\Result\Result[] $facets */
    $facets = array_reduce($results, function ($carry, $item) {
      /** @var \Drupal\facets\Result\Result $item */
      $carry[$item->getRawValue()] = $item;
      return $carry;
    }, []);


    array_walk($facet_groups, function ($config) use ($results, &$facets) {
      /** @var array $types */
      $types = array_filter($config['parent_group']);
      if (empty($types)) {
        return;
      }

      /** @var array $filtered */
      $filtered = array_filter($types, function ($type) use ($facets) {
        return array_key_exists($type, $facets);
      });
      if (empty($filtered)) {
        return;
      }


   

      // Walk-through all remain filtered types.
      foreach ($facets as $fkey => $item) {
        if (!in_array($fkey, $filtered)) {
	  unset($facets[$fkey]);
        }
      }


    });


    return array_values($facets);
  }

  /**
   * Setter method to deting entity type manager property.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function setEntityTypeManager(EntityTypeManager $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    // Inject dependency into current plugin's instance.
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }
}
