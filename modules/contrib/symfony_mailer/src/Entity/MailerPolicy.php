<?php

namespace Drupal\symfony_mailer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\symfony_mailer\Annotation\EmailBuilder;
use Drupal\symfony_mailer\Processor\AdjusterPluginCollection;

/**
 * Defines a Mailer Policy configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "mailer_policy",
 *   label = @Translation("Mailer Policy"),
 *   handlers = {
 *     "list_builder" = "Drupal\symfony_mailer\MailerPolicyListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\symfony_mailer\Form\PolicyEditForm",
 *       "add" = "Drupal\symfony_mailer\Form\PolicyAddForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer mailer",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/mailer/policy/{mailer_policy}",
 *     "delete-form" = "/admin/config/system/mailer/policy/{mailer_policy}/delete",
 *     "collection" = "/admin/config/system/mailer/policy",
 *   },
 *   config_export = {
 *     "id",
 *     "configuration",
 *   }
 * )
 */
class MailerPolicy extends ConfigEntityBase implements EntityWithPluginCollectionInterface {

  use StringTranslationTrait;

  /**
   * Maximum length of a summary for a adjuster.
   *
   * @var int
   */
  protected const MAX_SUMMARY = 50;

  /**
   * The unique ID of the policy record.
   *
   * @var string
   */
  protected $id;

  /**
   * The email builder manager.
   *
   * @var \Drupal\symfony_mailer\Processor\EmailBuilderManagerInterface
   */
  protected $emailBuilderManager;

  /**
   * The email adjuster manager.
   *
   * @var \Drupal\symfony_mailer\Processor\EmailAdjusterManager
   */
  protected $emailAdjusterManager;

  protected $labelUnknown;
  protected $labelAll;
  protected $type;
  protected $subType;
  protected $entityId;
  protected $entity;
  protected $builderDefinition;

  /**
   * Email builder configuration for this policy record.
   *
   * An associative array of email adjuster configuration, keyed by the plug-in
   * ID with value as an array of configured settings.
   */
  protected $configuration = [];

  /**
   * The collection of email adjuster plug-ins configured in this policy.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->emailBuilderManager = \Drupal::service('plugin.manager.email_builder');
    $this->emailAdjusterManager = \Drupal::service('plugin.manager.email_adjuster');
    $this->labelUnknown = $this->t('Unknown');
    $this->labelAll = $this->t('<b>*All*</b>');

    // The root policy with ID '_' applies to all types.
    if (!$this->id || ($this->id == '_')) {
      $this->builderDefinition = (new EmailBuilder(['label' => $this->labelAll]))->get();
      return;
    }

    [$this->type, $this->subType, $this->entityId] = array_pad(explode('.', $this->id), 3, NULL);
    $this->builderDefinition = $this->emailBuilderManager->getDefinition($this->type, FALSE);
    if (!$this->builderDefinition) {
      $this->builderDefinition = ['label' => $this->labelUnknown];
    }
    if ($this->entityId && !empty($this->builderDefinition['has_entity'])) {
      $this->entity = $this->entityTypeManager()->getStorage($this->type)->load($this->entityId);
    }
  }

  /**
   * Gets the email type this policy applies to.
   *
   * @return string
   *   Email type, or NULL if the policy applies to all types.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets the email sub-type this policy applies to.
   *
   * @return string
   *   Email sub-type, or NULL if the policy applies to all sub-types.
   */
  public function getSubType() {
    return $this->subType;
  }

  /**
   * Gets the config entity this policy applies to.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   Entity, or NULL if the policy applies to all entities.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets a human-readable label for the email type this policy applies to.
   *
   * @return string
   *   Email type label.
   */
  public function getTypeLabel() {
    return $this->builderDefinition['label'];
  }

  /**
   * Gets a human-readable label for the the email sub-type.
   *
   * @return string
   *   Email sub-type label.
   */
  public function getSubTypeLabel() {
    if ($this->subType) {
      if ($sub_types = $this->builderDefinition['sub_types'] ?? []) {
        return $sub_types[$this->subType] ?? $this->labelUnknown;
      }
      return $this->subType;
    }
    return $this->labelAll;
  }

  /**
   * Gets a human-readable label for the config entity this policy applies to.
   *
   * @return string
   *   Email config entity label, or NULL if the builder doesn't support
   *   entities.
   */
  public function getEntityLabel() {
    if (empty($this->builderDefinition['has_entity'])) {
      return NULL;
    }
    if ($this->entity) {
      return $this->entity->label();
    }
    return $this->entityId ? $this->labelUnknown : $this->labelAll;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $labels = [$this->getTypeLabel(), $this->getSubTypeLabel(), $this->getEntityLabel()];
    return implode(' » ', array_filter($labels));
  }

  /**
   * Sets the email adjuster configuration for this policy record.
   *
   * @param array $configuration
   *   An associative array of adjuster configuration, keyed by the plug-in ID
   *   with value as an array of configured settings.
   *
   * @return $this
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    if ($this->pluginCollection) {
      $this->pluginCollection->setConfiguration($configuration);
    }
    return $this;
  }

  /**
   * Gets the email adjuster configuration for this policy record.
   *
   * @return array
   *   An associative array of adjuster configuration, keyed by the plug-in ID
   *   with value as an array of configured settings.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Returns the ordered collection of configured adjuster plugin instances.
   *
   * @return \Drupal\symfony_mailer\Processor\AdjusterPluginCollection
   *   The adjuster collection.
   */
  public function adjusters() {
    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = new AdjusterPluginCollection($this->emailAdjusterManager, $this->configuration);
    }
    return $this->pluginCollection;
  }

  /**
   * Returns all available adjuster plugin definitions.
   *
   * @return array
   *   An associative array of plugin definitions, keyed by the plug-in ID.
   */
  public function adjusterDefinitions() {
    return $this->emailAdjusterManager->getDefinitions();
  }

  /**
   * Gets a short human-readable summary of the configured policy.
   *
   * @param bool $expanded
   *   (Optional) If FALSE return just the labels. If TRUE include a short
   *   summary of each element.
   *
   * @return string
   *   Summary text.
   */
  public function getSummary($expanded = FALSE) {
    $summary = [];
    $separator = ', ';

    foreach ($this->adjusters()->sort() as $adjuster) {
      $element = $adjuster->getLabel();
      if ($expanded && ($element_summary = $adjuster->getSummary())) {
        if (strlen($element_summary) > static::MAX_SUMMARY) {
          $element_summary = substr($element_summary, 0, static::MAX_SUMMARY) . '…';
        }
        $element .= ": $element_summary";
        $separator = '<br>';
      }
      $summary[] = $element;

    }

    return implode($separator, $summary);
  }

  /**
   * Returns the common adjusters for this policy.
   *
   * @return array
   *   An array of common adjuster IDs.
   */
  public function getCommonAdjusters() {
    return $this->builderDefinition ? $this->builderDefinition['common_adjusters'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['adjusters' => $this->adjusters()];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if ($this->entity) {
      $this->addDependency('config', $this->entity->getConfigDependencyName());
    }
    elseif ($provider = $this->builderDefinition['provider'] ?? NULL) {
      $this->addDependency('module', $provider);
    }
    return $this;
  }

  /**
   * Helper callback to sort entities.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    return strnatcasecmp($a->getTypeLabel(), $b->getTypeLabel()) ?:
      strnatcasecmp($a->getSubTypeLabel(), $b->getSubTypeLabel()) ?:
      strnatcasecmp($a->getEntityLabel(), $b->getEntityLabel());
  }

  /**
   * Loads a Mailer Policy, or creates a new one.
   *
   * @param string $id
   *   The id of the policy to load or create.
   *
   * @return static
   *   The policy object.
   */
  public static function loadOrCreate(string $id) {
    return static::load($id) ?? static::create(['id' => $id]);
  }

  /**
   * Loads config for a Mailer Policy including inherited policy.
   *
   * @param string $id
   *   The id of the policy.
   *
   * @return array
   *   The configuration array.
   */
  public static function loadInheritedConfig(string $id) {
    $config = [];
    for ($loop_id = $id; $loop_id; $loop_id = static::parentId($loop_id)) {
      if ($policy = MailerPolicy::load($loop_id)) {
        $config += $policy->getConfiguration();
      }

    }
    return $config;
  }

  /**
   * Imports a Mailer Policy from configuration.
   *
   * @param string $id
   *   The id of the policy to import.
   * @param array $configuration
   *   An associative array of adjuster configuration, keyed by the plug-in ID
   *   with value as an array of configured settings.
   */
  public static function import($id, array $configuration) {
    $policy = static::loadOrCreate($id);
    $configuration += $policy->getConfiguration();

    $inherited = static::loadInheritedConfig(static::parentId($id));
    foreach (array_keys($configuration) as $key) {
      if (isset($inherited[$key]) && static::identicalArray($configuration[$key], $inherited[$key])) {
        unset($configuration[$key]);
      }
    }

    if ($configuration) {
      $policy->setConfiguration($configuration)->save();
    }
    else {
      $policy->delete();
    }
  }

  /**
   * Returns the parent ID.
   *
   * @param string $id
   *   The initial id.
   *
   * @return string
   *   The parent id.
   */
  protected static function parentId(string $id) {
    if ($id == '_') {
      return NULL;
    }
    $pos = strrpos($id, '.');
    return $pos ? substr($id, 0, $pos) : '_';
  }

  /**
   * Compares two arrays recursively.
   *
   * @param array $a
   *   The first array.
   * @param array $b
   *   The second array.
   *
   * @return bool
   *   TRUE if the arrays are identical.
   */
  protected static function identicalArray(array $a, array $b) {
    if (count($a) != count($b)) {
      return FALSE;
    }

    foreach ($a as $key => $value_a) {
      if (!isset($b[$key])) {
        return FALSE;
      }
      $value_b = $b[$key];
      if (is_array($value_a) && is_array($value_b)) {
        if (!static::identicalArray($value_a, $value_b)) {
          return FALSE;
        }
      }
      elseif ($value_a != $value_b) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
