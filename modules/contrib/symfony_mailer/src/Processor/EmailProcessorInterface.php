<?php

namespace Drupal\symfony_mailer\Processor;

use Drupal\symfony_mailer\EmailInterface;

/**
 * Defines the interface for Email Processors.
 */
interface EmailProcessorInterface {

  /**
   * Mapping from phase to default function name.
   *
   * @var string[]
   */
  public const FUNCTION_NAMES = [
    EmailInterface::PHASE_BUILD => 'build',
    EmailInterface::PHASE_PRE_RENDER => 'preRender',
    EmailInterface::PHASE_POST_RENDER => 'postRender',
  ];

  /**
   * Initializes an email to call this email processor.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to initialize.
   */
  public function init(EmailInterface $email);

}
