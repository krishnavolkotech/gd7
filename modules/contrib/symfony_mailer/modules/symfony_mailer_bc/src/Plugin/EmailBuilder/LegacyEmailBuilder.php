<?php

namespace Drupal\symfony_mailer_bc\Plugin\EmailBuilder;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Exception\SkipMailException;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Legacy Email Builder plug-in that calls hook_mail().
 */
class LegacyEmailBuilder extends EmailBuilderBase implements ContainerFactoryPluginInterface {

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(EmailFactoryInterface $factory, array $message) {
    $message += [
      'subject' => '',
      'body' => [],
      'headers' => [],
    ];

    // Call hook_mail() on this module.
    if (function_exists($function = $message['module'] . '_mail')) {
      $function($message['key'], $message, $message['params']);
    }

    if (isset($message['send']) && !$message['send']) {
      throw new SkipMailException('Send aborted by hook_mail().');
    }

    $email = $factory->newModuleEmail($message['module'], $message['key']);
    $this->mailManager->emailFromArray($email, $message);
    return $email;
  }

}
