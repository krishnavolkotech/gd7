<?php

declare(strict_types = 1);

namespace Drupal\matomo_tagmanager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Matomo Tag Manager container.
 */
interface ContainerInterface extends ConfigEntityInterface {

  /**
   * Get the ID of the container.
   *
   * @return string
   *   The configured container ID.
   */
  public function containerId(): string;

  /**
   * Get the container's weight.
   *
   * @return int
   *   The weight of the container.
   */
  public function weight(): int;

}
