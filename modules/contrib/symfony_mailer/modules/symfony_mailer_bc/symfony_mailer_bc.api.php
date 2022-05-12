<?php

/**
 * @file
 * Documentation of Symfony Mailer back-compatibility hooks.
 */

/**
 * Alters back-compatibility creation of an email.
 *
 * The parameters supplied are from the old mail manager interface. The altered
 * values will be passed to the email factory.
 *
 * @param array $message
 *   A message array, as described in hook_mail_alter().
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
 *   (optional) Entity. @see \Drupal\symfony_mailer\EmailInterface::getEntity()
 *
 * @see \Drupal\Core\Mail\MailManagerInterface::mail()
 * @see \Drupal\symfony_mailer\EmailFactory
 */
function hook_mailer_bc_alter(array &$message, ?ConfigEntityInterface $entity) {
}

/**
 * Alters back-compatibility creation of an email from a specific module.
 *
 * @param array $message
 *   A message array, as described in hook_mail_alter().
 * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
 *   (optional) Entity. @see \Drupal\symfony_mailer\EmailInterface::getEntity()
 */
function hook_mailer_bc_MODULE_alter(array &$message, ?ConfigEntityInterface $entity) {
}
