<?php

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Class MigrationFormBase.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Construct the MigrationGroupFormBase.
   *
   * For simple entity forms, there's no need for a constructor. Our migration form
   * base, however, requires an entity query factory to be injected into it
   * from the container. We later use this query factory to build an entity
   * query for the exists() method.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory for the migration group entity type.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * Factory method for MigrationFormBase.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A container interface service.
   *
   * @return \Drupal\migrate_tools\Form\MigrationGroupFormBase
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the migration add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    /** @var MigrationInterface $migration */
    $migration = $this->entity;

    $form['warning'] = [
      '#markup' => $this->t('Creating migrations is not yet supported. See <a href=":url">:url</a>', [
        ':url' => 'https://www.drupal.org/node/2573241',
      ])
    ];

    // Build the form.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $migration->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $migration->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
      '#disabled' => !$migration->isNew(),
    );

    $groups = MigrationGroup::loadMultiple();
    $group_options = [];
    foreach ($groups as $group) {
      $group_options[$group->id()] = $group->label();
    }
    if (!$migration->get('migration_group') && isset($group_options['default'])) {
      $migration->set('migration_group', 'default');
    }

    $form['migration_group'] = array(
      '#type' => 'select',
      '#title' => $this->t('Migration Group'),
      '#empty_value' => '',
      '#default_value' => $migration->get('migration_group'),
      '#options' => $group_options,
      '#description' => $this->t('Assign this migration to an existing group.'),
    );

    return $form;
  }

  /**
   * Checks for an existing migration group.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new migration entity query.
    $query = $this->entityQueryFactory->get('migration');

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return $this
   */
  public function save(array $form, FormStateInterface $form_state) {
    $migration = $this->getEntity();
    $status = $migration->save();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $message = $this->t('Migration %label has been updated.', array('%label' => $migration->label()));
      \Drupal::messenger()->addStatus($message);
    }
    else {
      // If we created a new entity...
      $message = $this->t('Migration %label has been added.', array('%label' => $migration->label()));
      \Drupal::messenger()->addStatus($message);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.migration.list',
      array('migration_group' => $migration->get('migration_group')));
  }

}
