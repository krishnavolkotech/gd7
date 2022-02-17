<?php

namespace Drupal\hzd_react\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxController extends ControllerBase {

  public function call_ajax() {

    

    $response["eigenschaft"] = "Hallo Welt";

    return new JsonResponse($response);
  }

}