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
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Adds an link to deployment information for releases.
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentUser($container->get('current_user'));
    $provider->setEntityTypeManager($container->get('entity_type.manager'));

    return $provider;
  }

  /**
   * Sets the entityTypeManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   */
  public function setEntityTypeManager(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
    $access = $this->currentUser->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();

    // Nid of the release.
    $releaseNid = $context->getField("drupal_internal__nid")->value;

    // Use nid of the release to find associated deployed releases.
    /** @var string[] $deployedReleases - Nids of deployed releases. */
    $deployedReleases = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'deployed_releases')
      ->condition('field_deployed_release', $releaseNid)
      ->execute();

    $serviceNid = $context->getField("field_relese_services")->entity->id();

    $url = Url::fromUri('base:/release-management/releases/einsatzinformationen',['query' => [
      'service' => $serviceNid,
      'release' => $releaseNid,
      'deploymentStatus' => 'all',
      'state' => 1,
    ]]);

    // Provide cacheability.
    $link_cacheability = new CacheableMetadata();

    if (count($deployedReleases) == 0) {
      return AccessRestrictedLink::createInaccessibleLink($link_cacheability);
    }

    return AccessRestrictedLink::createLink($access, $link_cacheability, $url, $this->getLinkRelationType(), [
      'deployedReleases' => $deployedReleases,
      'deployedReleasesCount' => count($deployedReleases),
    ]);
    
  }

}
