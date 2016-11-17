<?php

namespace Drupal\entity_print\Event;

use Drupal\entity_print\Plugin\PrintEngineInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class PrintEventBase extends Event {

  /**
   * @var \Drupal\entity_print\Plugin\PrintEngineInterface
   */
  protected $printEngine;

  /**
   * The Print Engine event base class.
   *
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The Print Engine.
   */
  public function __construct(PrintEngineInterface $print_engine) {
    $this->printEngine = $print_engine;
  }

  /**
   * Gets the Print Engine plugin that will print the Print.
   *
   * @return \Drupal\entity_print\Plugin\PrintEngineInterface
   *   The Print Engine.
   */
  public function getPrintEngine() {
    return $this->printEngine;
  }

}
