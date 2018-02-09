<?php

namespace Drupal\cust_group\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\cust_group\Imce;
use Drupal\imce\Controller\ImceController as ImceControllerBase;

/**
 * Controller routines for imce routes.
 */
class ImceController extends ImceControllerBase {

  /**
   * Returns an administrative overview of Imce Profiles.
   */
  public function adminOverview(Request $request) {
    // Build the settings form first.(may redirect)
    $output['settings_form'] = \Drupal::formBuilder()->getForm('Drupal\imce\Form\ImceSettingsForm') + ['#weight' => 10];
    // Buld profile list.
    $output['profile_list'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['imce-profile-list']],
      'title' => ['#markup' => '<h2>' . $this->t('Configuration Profiles') . '</h2>'],
      'list' => $this->entityTypeManager()->getListBuilder('imce_profile')->render(),
    ];
    return $output;
  }

  /**
   * Handles requests to /imce/{scheme} path.
   */
  public function page($group, Request $request) {
    // pr($request->getMethod());exit;
    // pr($group);
    // pr(Imce::response($request, $this->currentUser(), $scheme));exit;
    return Imce::response($request, $this->currentUser(), 'private');
  }

  /**
   * Checks access to /imce/{scheme} path.
   */
  public function checkAccess($group) {
    return AccessResult::allowedIf(Imce::access($this->currentUser(), 'private'));
  }

}
