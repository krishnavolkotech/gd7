<?php

namespace Drupal\symfony_mailer\Processor;

use Drupal\Core\Plugin\PluginBase;
use Drupal\symfony_mailer\EmailInterface;

/**
 * Defines the base class for EmailProcessorInterface implementations.
 *
 * This base class is for plug-ins. Use EmailProcessorCustomBase for custom
 * processors.
 */
class EmailProcessorBase extends PluginBase implements EmailProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function init(EmailInterface $email) {
    foreach (self::FUNCTION_NAMES as $phase => $function) {
      if (method_exists($this, $function)) {
        $email->addProcessor([$this, $function], $phase, $this->getWeight($phase), $this->getPluginId());
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
    $weight = $this->getPluginDefinition()['weight'] ?? static::DEFAULT_WEIGHT;
    return is_array($weight) ? $weight[$phase] : $weight;
  }

}
