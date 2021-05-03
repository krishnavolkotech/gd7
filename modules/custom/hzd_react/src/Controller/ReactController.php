<?php

namespace Drupal\hzd_react\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines ReactController class.
 */
class ReactController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content($reactPage = NULL) {
    return [
      '#type' => 'markup',
      '#markup' => '<div id="react-app"></div>',
      '#attached' => ['library' => 'hzd_react/react_app_dev'],
    ];
  }

}
