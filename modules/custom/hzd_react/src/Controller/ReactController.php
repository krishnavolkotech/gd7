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
      0 => '<Verfahren>',
    ];

    $query1 = \Drupal::service('entity.query');
    $result1 = $query->get('node')
      ->condition('type', 'services')
      ->condition('release_type' , 459)
      ->condition('field_release_name', '', '!=')
      ->execute();

    foreach ($result1 as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $services[$nid] = $node->title->value;
    }

    //Get releases
    $releases = [
      0 => '<Release>',
    ];

    /*
      Conditions:
        - Typ: Release
        - nicht archiviert
        - nicht gesperrt
        - nicht zurückgewiesen
        - Interner Status 1 oder 2
    */
    $query = \Drupal::service('entity.query');
    $result2 = $query->get('node')
      ->condition('type', 'release')
      ->condition('field_release_type', 3, '<')
      ->sort('title', 'ASC')
      ->execute();

    // foreach ($result2 as $element => $nid) {
    //   $node = $this->entityTypeManager->getStorage('node')->load($nid);
    //   $releases[$nid] = $node->title->value;
    // }
    $releases = [];
    foreach ($result2 as $element => $nid) {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $referencedEntities = $node->field_relese_services->referencedEntities();
      if (count($referencedEntities) > 0) {
        $serviceId = $referencedEntities[0]->id();
      }
      else {
        $serviceId = "error";
      }
      $releases[$serviceId][] = [$node->id() => $node->title->value];
    }

    // Get states from database.
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
        ],
      ],
    ];

    return $build;
  }
}
