<?php

namespace Drupal\hzd_react\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteMatchInterface;

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
   * Returns role based on membership in ZRML-Group.
   * 
   * @return string $role
   *  The group role.
   */
  protected function getRole() {
    $role = '';
    $group = Group::load(Zentrale_Release_Manager_Lander);
    $member = $group->getMember(\Drupal::currentUser());
    if ($member && group_request_status($member)) {
      $roles = $member->getRoles();
      $role = "ZRML";
      if (in_array($group->getGroupType()->id() . '-admin', array_keys($roles))) {
        $role = "ZRMK";
      }
    }
    
    if (array_intersect(['site_administrator','administrator'], \Drupal::currentUser()->getRoles())) {
      $role = "SITE-ADMIN";
    }
    return $role;
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content(RouteMatchInterface $routeMatch) {
    // if siteadmin

    // if rm group admin zrml

    // elseif member zrml

    // get role -> drupalSettings (site admin > group admin rm > zrml)
    // get Verfahren
    $groupId = $routeMatch->getParameter('group');

    $build = [
      '#type' => 'markup',
      '#markup' => '<div id="react-app"></div>',
      // '#attached' => ['library' => 'hzd_react/react_app_dev'],
      '#attached' => ['library' => 'hzd_react/react_app'],
    ];

    // In Zukunft noch um Geschäftsservice und Sonstiges Projekt ergänzen.
    $serviceTypes = [459, 460];
  
    $services = [];
    foreach ($serviceTypes as $value) {
      $serviceQuery = db_query("SELECT n.title, n.nid
        FROM {node_field_data} n, {group_releases_view} grv, 
        {node__release_type} nrt 
        WHERE n.nid = grv.service_id and n.nid = nrt.entity_id 
        and grv.group_id = :gid and nrt.release_type_target_id = :tid 
        ORDER BY n.title asc", array(
          ":gid" => $groupId,
          ":tid" => $value,
        )
      )->fetchAll();
      
      foreach ($serviceQuery as $services_data) {
        $serviceNode = $services_data->nid;
        $services[$value][$services_data->nid] = node_get_title_fast([$serviceNode])[$serviceNode];
      }
    }

    // Rolle: site-admin, zrmk, zrml?
    $role = $this->getRole();

    $build['#attached']['drupalSettings']['role'] = $role;
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
    // Get environments(0.0579s).
    $startTime = microtime(true);
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
    $envTime = number_format(( microtime(true) - $startTime), 4);

    // Get KONSENS Service names (0.0489s).
    $startTime = microtime(true);
    $services = [
      0 => ['<Verfahren>', 0],
    ];

    $query = \Drupal::service('entity.query');
    $result = $query->get('node')
      ->condition('type', 'services')
      // ->condition('release_type' , 459)
      ->condition('field_release_name', '', '!=')
      ->execute();

    foreach ($result as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $services[$nid][] = $node->title->value;
      $services[$nid][] = $node->uuid();
    }
    $srvTime = number_format(( microtime(true) - $startTime), 4);
    
    $user_state = hzd_user_state($uid = NULL);
    // Get previous releases' names (4.2647s !!!)
    // @todo performance verbessern
    /*
    $startTime = microtime(true);
    $prevReleases=[];


    $query3 = \Drupal::service('entity.query');
    $result3 = $query->get('node')
      ->condition('type', 'deployed_releases')
      ->condition('field_deployment_status', '1')
      ->condition('field_user_state', $user_state)
      ->execute();

    foreach ($result3 as $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      //Release:
      $referencedEntities = $node->field_deployed_release->referencedEntities();
      //Verfahren:
      $referencedEntities2 = $node->field_service->referencedEntities();

      $serviceId = $referencedEntities2[0]->id();
      $prevReleases[$serviceId][$referencedEntities[0]->id()] =[
        $referencedEntities[0]->uuid(),
        $referencedEntities[0]->title->value,
      ];
    }
    $prevTime = number_format(( microtime(true) - $startTime), 4);
*/
    /*
      Conditions:
        - Typ: Release
        - nicht archiviert
        - nicht gesperrt
        - nicht zurückgewiesen
        - Interner Status 1 oder 2
    */
    // Releases
    /*
    Structure:
      $releases[$serviceId][$node->id()] = [
        $node->uuid(),
        $node->title->value,
      ];
    */
    /*
    $startTime = microtime(true);

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
      $releases[$serviceId][$node->id()] = [
        $node->uuid(),
        $node->title->value,
      ];
    }
    $relTime = number_format(( microtime(true) - $startTime), 4);
*/
    // Get states from database (0.0005s)
    $startTime = microtime(true);
    $database = \Drupal::database();
    $states = $database->query("SELECT id, state, abbr FROM states WHERE id < 19")
      ->fetchAll();

    $finalStates = [];
    foreach ($states as $state) {
      $finalStates[intval($state->id)] = $state->state;
      $finalStates[intval($state->id)] .= $state->abbr ? ' (' . $state->abbr . ')' : '';
    }
    $stTime = number_format(( microtime(true) - $startTime), 4);

    // Rolle: site-admin, zrmk, zrml?
    $role = $this->getRole();

    $build = [
      '#type' => 'markup',
      '#markup' => '<div id="react-app"></div>',
      '#attached' => [
        // 'library' => 'hzd_react/react_app_dev',
        'library' => 'hzd_react/react_app',
        'drupalSettings' => [
          'environments' => $environments,
          'states' => $finalStates,
          'services' => $services,
          // 'releases' => $releases,
          // 'prevReleases' => $prevReleases,
          'userstate' => $user_state,
          'role' => $role,
        ],
      ],
    ];

    return $build;
  }
}
