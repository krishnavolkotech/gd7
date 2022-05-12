<?php

namespace Drupal\symfony_mailer_bc;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\MailerHelperInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderManagerInterface;

/**
 * Provides a Symfony Mailer replacement for MailManager.
 */
class MailManagerReplacement extends MailManager {

  /**
   * List of headers for conversion to/from array.
   *
   * @var array
   */
  protected const HEADERS = [
    'From' => 'from',
    'Reply-To' => 'reply-to',
    'To' => 'to',
    'Cc' => 'cc',
    'Bcc' => 'bcc',
  ];

  /**
   * The email factory.
   *
   * @var \Drupal\symfony_mailer\EmailFactoryInterface
   */
  protected $emailFactory;

  /**
   * The mailer helper.
   *
   * @var \Drupal\symfony_mailer\MailerHelperInterface
   */
  protected $mailerHelper;

  /**
   * The email builder manager.
   *
   * @var \Drupal\symfony_mailer\Processor\EmailBuilderManagerInterface
   */
  protected $emailBuilderManager;

  /**
   * Constructs the MailManagerReplacement object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\symfony_mailer\EmailFactoryInterface $email_factory;
   *   The email factory.
   * @param \Drupal\symfony_mailer\MailerHelperInterface $mailer_helper
   *   The mailer helper.
   * @param \Drupal\symfony_mailer\Processor\EmailBuilderManagerInterface $email_builder_manager
   *   The email builder manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation, RendererInterface $renderer, EmailFactoryInterface $email_factory, MailerHelperInterface $mailer_helper, EmailBuilderManagerInterface $email_builder_manager) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $config_factory, $logger_factory, $string_translation, $renderer);
    $this->emailFactory = $email_factory;
    $this->mailerHelper = $mailer_helper;
    $this->emailBuilderManager = $email_builder_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function mail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) {
    $message = [
      'id' => $module . '_' . $key,
      'module' => $module,
      'key' => $key,
      'to' => $to,
      'langcode' => $langcode,
      'params' => $params,
      'reply-to' => $reply,
      'send' => $send,
    ];

    // Create an email from the array. Always call the plug-in from the module
    // name, not any variants.
    $email = $this->emailBuilderManager->createInstance($module)->fromArray($this->emailFactory, $message);

    if ($send) {
      $message['result'] = $email->send();
    }

    // Update the message array.
    $message['subject'] = $email->getSubject();
    $message['body'] = ($email->getPhase() >= EmailInterface::PHASE_POST_RENDER) ? $email->getHtmlBody() : $email->getBody();

    $headers = $email->getHeaders();
    foreach (self::HEADERS as $name => $key) {
      if ($headers->has($name)) {
        $message['headers'][$name] = $headers->get($name)->getBodyAsString();
      }
      if ($key) {
        $message[$key] = $message['headers'][$name] ?? NULL;
      }
    }

    return $message;
  }

  /**
   * Fills an Email from a message array.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to fill.
   * @param array $message
   *   The array to fill from.
   */
  public function emailFromArray(EmailInterface $email, array $message) {
    $email->setSubject($message['subject']);

    if ($email->getPhase() == EmailInterface::PHASE_INIT) {
      $email->setParams($message['params']);
    }

    // Address headers.
    $headers = $email->getHeaders();
    foreach (self::HEADERS as $name => $key) {
      $encoded = $message['headers'][$name] ?? $message[$key] ?? NULL;
      if (isset($encoded)) {
        $email->setAddress($name, $this->mailerHelper->parseAddress($encoded, $message['langcode']));
      }
    }

    // Body.
    if (is_array($message['body'])) {
      foreach ($message['body'] as $part) {
        if ($part instanceof MarkupInterface) {
          $content = ['#markup' => $part];
        }
        else {
          $content = [
            '#type' => 'processed_text',
            '#text' => $part,
          ];
        }
        $email->appendBody($content);
      }
    }
    else {
      $email->setHtmlBody($message['body']);
    }
  }

}
