<?php

namespace Drupal\symfony_mailer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for the Email Factory.
 */
interface EmailFactoryInterface {

  /**
   * Sends an email unrelated to a config entity.
   *
   * @param string $module
   *   The module name.
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\EmailInterface::getSubType()
   * @param ... $params
   *   Parameters for building this email.
   *
   * @return \Drupal\symfony_mailer\EmailInterface
   *   A new email object.
   */
  public function sendModuleEmail(string $module, string $sub_type, ...$params);

  /**
   * Send an email related to a config entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   Entity. @see \Drupal\symfony_mailer\EmailInterface::getEntity()
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\EmailInterface::getSubType()
   * @param ... $params
   *   Parameters for building this email.
   *
   * @return \Drupal\symfony_mailer\EmailInterface
   *   A new email object.
   */
  public function sendEntityEmail(ConfigEntityInterface $entity, string $sub_type, ...$params);

  /**
   * Creates an email object unrelated to a config entity.
   *
   * The email is not sent, allowing the caller to modify it before sending.
   * Normally it is recommended to call ::sendModuleMail() instead, and allow
   * the EmailBuilder to create the mail.
   *
   * @param string $module
   *   The module name.
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\EmailInterface::getSubType()
   * @param ... $params
   *   Parameters for building this email.
   *
   * @return \Drupal\symfony_mailer\EmailInterface
   *   A new email object.
   */
  public function newModuleEmail(string $module, string $sub_type, ...$params);

  /**
   * Creates an email object related to a config entity.
   *
   * The email is not sent, allowing the caller to modify it before sending.
   * Normally it is recommended to call ::sendEntityMail() instead, and allow
   * the EmailBuilder to create the mail.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   Entity. @see \Drupal\symfony_mailer\EmailInterface::getEntity()
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\EmailInterface::getSubType()
   * @param ... $params
   *   Parameters for building this email.
   *
   * @return \Drupal\symfony_mailer\EmailInterface
   *   A new email object.
   */
  public function newEntityEmail(ConfigEntityInterface $entity, string $sub_type, ...$params);

}
