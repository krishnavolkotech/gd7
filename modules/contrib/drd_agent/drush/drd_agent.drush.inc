<?php

/**
 * Implements hook_drush_command().
 *
 * @return array
 */
function drd_agent_drush_command() {
  $items = [];

  $items['drd-agent-setup'] = [
    'arguments' => [
      'token' => 'Base64 encoded and serialized array of all variables required such that DRD can communicate with this domain in the future',
    ],
  ];

  return $items;
}

/**
 * Drush command to configure this domain for communication with a DRD instance.
 *
 * @param $token
 *
 * @see drd_agent_setup()
 */
function drush_drd_agent_setup($token) {
  $_SESSION['drd_agent_authorization_values'] = $token;
  $service = \Drupal::service('drd_agent.setup');
  $service->execute();
  unset($_SESSION['drd_agent_authorization_values']);
}
