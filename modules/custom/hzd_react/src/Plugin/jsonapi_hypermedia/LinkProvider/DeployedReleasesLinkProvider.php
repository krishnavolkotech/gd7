<?php

namespace Drupal\hzd_react\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Adds an link to early warnings for releases.
 *
 * This presumes that early warnings are not provided as relationsips to
 * releases.
 *
 * @JsonapiHypermediaLinkProvider(
 *   id = "hzd_react.deployed_releases",
 *   link_context = {
 *     "resource_object" = "node--release",
 *   }
 * )
 *
 */
final class DeployedReleasesLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentUser($container->get('current_user'));
    $provider->setEntityQuery($container->get('entity.query'));

    return $provider;
  }

  /**
   * Sets the Interface for entity queries.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The current account.
   */
  public function setEntityQuery(QueryFactory $entityQuery) {
    $this->entityQuery = $entityQuery;
  }

  /**
   * Sets the current account.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current account.
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkRelationType() {
    return 'deployed-releases';
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof JsonApiDocumentTopLevel);

    // @todo Use "is_authenticated" variable.
    $is_authenticated = $this->currentUser->isAuthenticated();
    /*
      nids?? early_warnings:
      "field_earlywarning_release": 9834,
      "field_release_service": 1166 
    */

    // Nid of the release.
    $releaseNid = $context->getField("drupal_internal__nid")->value;

    // Use nid of the release to find associated deployed releases.
    /** @var string[] $deployedReleases - Nids of deployed releases. */
    $deployedReleases = $this->entityQuery->get('node')
      ->condition('status', 1)
      ->condition('type', 'deployed_releases')
      ->condition('field_deployed_release', $releaseNid)
      ->execute();

    
    $deployed_imgpath = drupal_get_path('module', 'hzd_release_management') . '/images/e-icon.png';
    $deployed_img = "<img title='Einsatzinformationen anzeigen' class = 'e-info-icon' src = '/" . $deployed_imgpath . "'>";

    $groupId = RELEASE_MANAGEMENT;
    // $options = \Drupal::request()->query->all();
    // $options['services'] = $releases->field_relese_services->target_id;
    // $options['releases'] = $releases->id();
    // unset($options['form_id']);
    // unset($options['form_build_id']);
    // unset($options['page']);

    // $url = Url::fromRoute('hzd_release_management.deployedinfo', array('group' => $groupId), [
    //     'query' => $options
    // ]);
    $serviceNid = $context->getField("field_relese_services")->entity->id();

    $url = Url::fromUri('base:/release-management/releases/einsatzinformationen',['query' => [
      'service' => $serviceNid,
      'release' => $releaseNid,
      'deploymentStatus' => 'all',
      'state' => 1,
    ]]);

    // Provide cacheability.
    $link_cacheability = new CacheableMetadata();
    $link_cacheability->addCacheContexts(['session.exists', 'user.roles:anonymous']);

    // For debugging purposes only. Doesn't seem to work.
    // $link_cacheabili ty->setCacheMaxAge(1);

    if (count($deployedReleases) == 0) {
      return AccessRestrictedLink::createInaccessibleLink($link_cacheability);
    }

    return AccessRestrictedLink::createLink(AccessResult::allowed(), $link_cacheability, $url, $this->getLinkRelationType(), [
      'deployedReleases' => $deployedReleases,
      'deployedReleasesCount' => count($deployedReleases),
    ]);
    
  }

}
