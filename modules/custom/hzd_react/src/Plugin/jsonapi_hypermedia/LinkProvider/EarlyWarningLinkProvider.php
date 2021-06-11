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
    return 'appendix';
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

    // Use nid of the release to find associated Early Warnings.
    $earlyWarnings = $this->entityQuery->get('node')
      ->condition('status', 1)
      ->condition('type', 'early_warnings')
      ->condition('field_earlywarning_release', $releaseNid)
      ->execute();
    
    $serviceNid = $context->getField("field_relese_services")->entity->id();

    // @todo Identify correct release type (459 = KONSENS, 460 = Best/Fakt)
    $viewOptions = [
      "query" => [
        "services" => $serviceNid,
        "releases" => $releaseNid,
        "release_type" => 459,
      ],
      "attributes" => [
        "class" => 'view-earlywarning',
        "title" => t('Read Early Warnings for this release')
      ],
    ];
    $view_earlywarning_url = Url::fromRoute('hzd_earlywarnings.view_early_warnings', array('group' => 1), $viewOptions);

    $route_name = 'base:/';
    $login_url = Url::fromUri($route_name, ['query' => ['_format' => 'json']]);

    // Provide cacheability.
    $link_cacheability = new CacheableMetadata();
    $link_cacheability->addCacheContexts(['session.exists', 'user.roles:anonymous']);

    // For debugging purposes only. Doesn't seem to work.
    $link_cacheability->setCacheMaxAge(1);

    return AccessRestrictedLink::createLink(AccessResult::allowed(), $link_cacheability, $view_earlywarning_url, $this->getLinkRelationType(), [
      'earlyWarnings' => $earlyWarnings,
      'earlyWarningCount' => count($earlyWarnings),
    ]);
  }

}
