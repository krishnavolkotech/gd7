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
 * Adds an link to early warnings for releases.
 *
 * This presumes that early warnings are not provided as relationsips to
 * releases.
 *
 * @JsonapiHypermediaLinkProvider(
*    id = "hzd_react.early_warnings",
 *   link_context = {
 *     "resource_object" = "node--release",
 *   }
 * )
 *
 */
final class EarlyWarningLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

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
    return 'early-warnings';
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof JsonApiDocumentTopLevel);
    $access = $this->currentUser->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
    
    // Nid of the release.
    $releaseNid = $context->getField("drupal_internal__nid")->value;

    // Use nid of the release to find associated Early Warnings.
    $earlyWarnings = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'early_warnings')
      ->condition('field_release_ref', $releaseNid)
      ->execute();
    
    $serviceEntity = $context->getField("field_relese_services")->entity;  
    $serviceNid = $serviceEntity->id();
    $releaseTyp = $serviceEntity->release_type->entity->id();

    $url = Url::fromRoute('view.early_warnings_mit_ref.page_1',[],['query' => [
      'services' => $serviceNid,
      'releases' => $releaseNid,
      'type' => $releaseTyp,
    ]]);

    // Provide cacheability.
    $link_cacheability = new CacheableMetadata();

    if (count($earlyWarnings) == 0) {
      return AccessRestrictedLink::createInaccessibleLink($link_cacheability);
    }

    return AccessRestrictedLink::createLink(AccessResult::allowed(), $link_cacheability, $url, $this->getLinkRelationType(), [
      'earlyWarnings' => $earlyWarnings,
      'earlyWarningCount' => count($earlyWarnings),
    ]);
  }

}
