<?php

namespace Drupal\drd_agent;

/**
 * Class Setup.
 *
 * @package Drupal\drd_agent
 */
class Setup {

  protected $values;

  /**
   * @inheritDoc
   */
  public function __construct() {
    $values = strtr($_SESSION['drd_agent_authorization_values'], array('-' => '+', '_' => '/'));
    $this->values = unserialize(base64_decode($values));
  }


  public function execute() {
    $config = \Drupal::configFactory()->getEditable('drd_agent.settings');

    $authorised = $config->get('authorised');

    $this->values['timestamp'] = REQUEST_TIME;
    $this->values['ip'] = \Drupal::request()->getClientIp();
    $authorised[$this->values['uuid']] = $this->values;

    $config->set('authorised', $authorised)->save(TRUE);
    return $this->values;
  }

  public function getDomain() {
    return parse_url($this->values['redirect'], PHP_URL_HOST);
  }

}
