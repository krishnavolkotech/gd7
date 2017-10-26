<?php

namespace Drupal\drd_agent;

use GuzzleHttp\Client;

/**
 * Class Library.
 *
 * @package Drupal\drd_agent
 */
class Library {

  /**
   * Callback to download the DRD library if required,
   *
   * @param bool $force
   *
   * @throws \Exception
   */
  public function load($force = FALSE) {
    $archive = 'drd-' . $_SERVER['HTTP_X_DRD_VERSION'] . '.phar';
    $uri = 'temporary://' . $archive;
    if ($force || !file_exists($uri)) {
      // Send request
      try {
        $client = new Client(['base_uri' => 'http://cgit.drupalcode.org/drd_agent_lib/plain/' . $archive]);
        $response = $client->request('get');
      }
      catch (\Exception $ex) {
        throw new \Exception('Can not load DRD Library');
      }
      if ($response->getStatusCode() != 200) {
        throw new \Exception('DRD Library not available');
      }
      file_put_contents($uri, $response->getBody()->getContents());
    }
    require_once($uri);
  }

}
