<?php

namespace Drupal\piwik_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for system_test routes.
 */
class PiwikTestController extends ControllerBase {

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  public function drupalSetMessageTest() {
    // Set some messages.
    \Drupal::messenger()->addStatus(t('Example status message.'));
    \Drupal::messenger()->addWarning(t('Example warning message.'));
    \Drupal::messenger()->addError('Example error message.', 'error');
    \Drupal::messenger()->addStatus(t('Example error <em>message</em> with html tags and <a href="http://example.com/">link</a>.'));

    return [];
  }

}
