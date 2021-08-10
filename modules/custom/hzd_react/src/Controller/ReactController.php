<?php

namespace Drupal\hzd_react\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Defines ReactController class.
 */
class ReactController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;


  /**
   * ReactController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns Group entity object for a given group id.
   * 
   * @param int $groupId
   *  The ID of the target group.
   * 
   * @return \Drupal\group\Entity\Group|false
   *  The Group entity or FALSE if Group entity does not exist.
   */
  protected function getGroup(int $groupId) {
    $group = Group::load($groupId);
    return is_a($group, 'Group') ? $group : false;
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content($reactPage = NULL) {
    // if siteadmin

    // if rm group admin zrml

    // elseif member zrml

    // get role -> drupalSettings (site admin > group admin rm > zrml)
    // get Verfahren

    $build = [
      '#type' => 'markup',
      '#markup' => '<div id="react-app"></div>',
      '#attached' => ['library' => 'hzd_react/react_app_dev'],
    ];

    // In Zukunft noch um Geschäftsservice und Sonstiges Projekt ergänzen.
    $serviceTypes = [459, 460];
  
    foreach ($serviceTypes as $value) {
      $serviceQuery = db_query("SELECT n.title, n.nid
        FROM {node_field_data} n, {group_releases_view} grv, 
        {node__release_type} nrt 
        WHERE n.nid = grv.service_id and n.nid = nrt.entity_id 
        and grv.group_id = :gid and nrt.release_type_target_id = :tid 
        ORDER BY n.title asc", array(
          ":gid" => 1,
          ":tid" => $value,
        )
      )->fetchAll();
      
      foreach ($serviceQuery as $services_data) {
        $serviceNode = $services_data->nid;
        $services[$value][$services_data->nid] = node_get_title_fast([$serviceNode])[$serviceNode];
      }
    }

    $build['#attached']['drupalSettings']['userRole']['zrml'] = false;
    $build['#attached']['drupalSettings']['userRole']['rm-admin'] = false;
    $build['#attached']['drupalSettings']['userRole']['site-admin'] = false;
    $build['#attached']['drupalSettings']['services'] = $services;

    return $build;
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function deployedReleases($group ) {
    // Get environments.
    $query = \Drupal::service('entity.query');
    $result = $query->get('node')
    ->condition('type', 'non_production_environment')
    ->execute();
    
    $environments = [
      0 => '<Umgebung>',
      1 => 'Produktion',
      2 => 'Pilot',
    ];

    foreach ($result as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $environments[$nid] = $node->title->value;
    }

    // Get Service names.
    $services = [
      0 => ['<Verfahren>', 0],
    ];

    $query = \Drupal::service('entity.query');
    $result = $query->get('node')
      ->condition('type', 'services')
      ->condition('release_type' , 459)
      ->condition('field_release_name', '', '!=')
      ->execute();

    foreach ($result as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $services[$nid][] = $node->title->value;
      $services[$nid][] = $node->uuid();
    }


    //Alle Service mit langer id
    // $serviceslang = [];

    // $query = \Drupal::service('entity.query');
    // $result = $query->get('node')
    //   ->condition('type', 'services')
    //   ->condition('release_type' , 459)
    //   ->condition('field_release_name', '', '!=')
    //   ->execute();

    // foreach ($result as $element => $nid) {
    //   $node = $this->entityTypeManager->getStorage('node')->load($nid);
    //   $serviceslang[$nid] = $node->id;
    // }


    //Get releases
    // $prevreleases = [
    //   0 => '<Release>',
    // ];

    // Get previous releases' names

    $prevreleases=[];

    // @todo 10.08.2021 - prüfen, ob notwendig.
    function hzd_user_state($uid = NULL) {
      if (!$uid) {
        $account = \Drupal::currentUser();
        $uid = $account->id();
      }
      $state = db_query("SELECT state_id FROM {cust_profile} WHERE uid = :uid", array(":uid" => $uid))->fetchField();
      return $state;
    }

    $user_state = hzd_user_state($uid = NULL);

    $query3 = \Drupal::service('entity.query');
    $result3 = $query->get('node')
      ->condition('type', 'release_deployment')
      ->condition('field_is_archived', false)
      ->condition('field_user_state', $user_state)
      ->execute();

    //release it und titel aus einsatzmeldung als array?

    foreach ($result3 as $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      //Release:
      $referencedEntities = $node->field_deployed_release->referencedEntities();
      //Verfahren:
      $referencedEntities2 = $node->field_service->referencedEntities();

      $serviceId = $referencedEntities2[0]->id();
      $prevreleases[$serviceId][] =[$referencedEntities[0]->id(), $referencedEntities[0]->title->value ];
    }

    //Get prev releases with drupalid in order to find long id fpr the post request in EinsatzmeldungsFormular
  
    $prevreleaseslong=[];

    $query4 = \Drupal::service('entity.query');
    $result4 = $query->get('node')
      ->condition('type', 'release_deployment')
      ->condition('field_is_archived', false)
      ->condition('field_user_state', $user_state)
      ->execute();

    foreach ($result4 as $nid) {
      $node2 = $this->entityTypeManager->getStorage('node')->load($nid);
      //Release:
      $LreferencedEntities = $node2->field_deployed_release->referencedEntities();
      //Verfahren:
      $LreferencedEntities2 = $node2->field_service->referencedEntities();

      $prevreleaseslong[$LreferencedEntities[0]->id() ][] =[$LreferencedEntities[0]->uuid(), $LreferencedEntities[0]->title->value, $LreferencedEntities[0]->id() ];
    }

    //Get releases' names

    /*
      Conditions:
        - Typ: Release
        - nicht archiviert
        - nicht gesperrt
        - nicht zurückgewiesen
        - Interner Status 1 oder 2
    */
    $query = \Drupal::service('entity.query');
    $result = $query->get('node')
      ->condition('type', 'release')
      ->condition('field_release_type', 3, '<')
      ->sort('title', 'ASC')
      ->execute();

    $releases = [];
    foreach ($result as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $referencedEntities = $node->field_relese_services->referencedEntities();
      if (count($referencedEntities) > 0) {
        $serviceId = $referencedEntities[0]->id();
      }
      else {
        $serviceId = "error";
      }
      $releases[$serviceId][] = [$node->id() => $node->title->value, $node->uuid() ];
    }

    //Get releases with drupalid in order to find long id fpr the post request in EinsatzmeldungsFormular

    $query = \Drupal::service('entity.query');
    $result = $query->get('node')
      ->condition('type', 'release')
      ->condition('field_release_type', 3, '<')
      ->sort('title', 'ASC')
      ->execute();

    $releaseslong = [];
    foreach ($result as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $referencedEntities = $node->field_relese_services->referencedEntities();
      if (count($referencedEntities) > 0) {
        $serviceId = $referencedEntities[0]->id();
      }
      else {
        $serviceId = "error";
      }
      $releaseslong[$node->id()][] = [$node->title->value, $node->uuid() ];

    }

    // Get states from database

    $database = \Drupal::database();
    $states = $database->query("SELECT id, state, abbr FROM states WHERE id < 19")
      ->fetchAll();

    $finalStates = [];
    foreach ($states as $state) {
      $finalStates[intval($state->id)] = $state->state;
      $finalStates[intval($state->id)] .= $state->abbr ? ' (' . $state->abbr . ')' : '';
    }

    $build = [
      '#type' => 'markup',
      '#markup' => '<div id="react-app"></div>',
      '#attached' => [
        'library' => 'hzd_react/react_app_dev',
        'drupalSettings' => [
          'environments' => $environments,
          'states' => $finalStates,
          'services' => $services,
          'releases' => $releases,
          'prevreleases' => $prevreleases,
          'userstate' => $user_state,
          'prevreleaseslong' => $prevreleaseslong,
          'releaseslong' => $releaseslong,
        ],
      ],
    ];

    return $build;
  }
}
