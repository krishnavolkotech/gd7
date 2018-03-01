<?php

namespace Drupal\cust_group\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\cust_group\Imce;
use Drupal\imce\Controller\ImceController as ImceControllerBase;
use Drupal\group\Entity\Group;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

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


  public function fileAutocomplete(Request $request, $group, $bundle_name){
    $group = Group::load($group);
    $string = Unicode::strtolower($request->query->get('q'));
    $label = \Drupal::service('pathauto.alias_cleaner')->cleanString($group->label());
    $filesQuery = \Drupal::entityQuery('file');
    $filesQuery->condition('uri','private://gruppen/'.$label.'/'.$bundle_name.'%', 'LIKE');
    $filesQuery->condition('filename','%'.$string.'%', 'LIKE');
    $filesQuery = $filesQuery->execute();
    $matches = [];
    $files = \Drupal\file\Entity\File::loadMultiple($filesQuery);
    // pr(count($files));
    foreach ($files as $entity_id => $entity) {
      $label = $entity->label();
      $key = "$label [fid:$entity_id]";
      // Strip things like starting/trailing white spaces, line breaks and
      // tags.
      $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
      // Names containing commas or quotes must be wrapped in quotes.
      $matches[] = array('value' => $key, 'label' => $label);
    }
    // pr(count($matches));exit;
    return new JsonResponse($matches);
    
  }
}
