<?php

namespace Drupal\symfony_mailer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an EmailBuilder item annotation object.
 *
 * @Annotation
 */
class EmailBuilder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Array of sub-types.
   *
   * The array key is the sub-type value and the value is the human-readable
   * label.
   *
   * @var string[]
   */
  public $sub_types = [];

  /**
   * Whether the plugin is associated with a config entity.
   *
   * @var bool
   */
  public $has_entity = FALSE;

  /**
   * Array of common adjuster IDs.
   *
   * @var string[]
   */
  public $common_adjusters = [];

  /**
   * Human-readable name of config to import.
   *
   * @var string
   */
  public $import = '';

  /**
   * Human-readable warning for importing.
   *
   * @var string
   */
  public $import_warning = '';

}
