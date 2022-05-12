<?php

namespace Drupal\symfony_mailer\Processor;

use Drupal\symfony_mailer\EmailInterface;

/**
 * Defines the base class for custom EmailProcessorInterface implementations.
 *
 * This base class is for custom processors that are not plug-ins. Use
 * EmailProcessorBase for plug-ins.
 */
class EmailProcessorCustomBase implements EmailProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function init(EmailInterface $email) {
    foreach (self::FUNCTION_NAMES as $phase => $function) {
      if (method_exists($this, $function)) {
        $email->addProcessor([$this, $function], $phase, $this->getWeight($phase), static::class);
      }
    }
  }

  /**
   * Gets the weight of the email processor.
   *
   * @param int $phase
   *   The phase that will run, one of the EmailInterface::PHASE_ constants.
   *
   * @return int
   *   The weight.
   */
  protected function getWeight(int $phase) {
    return EmailInterface::DEFAULT_WEIGHT;
  }

}
