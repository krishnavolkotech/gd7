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
use Drupal\cust_group\Controller\AccessController;
use Drupal\group\Entity\Group;
use Drupal\user\UserData;

/**
 * Adds an link to early warnings for releases.
 *
 * This presumes that early warnings are not provided as relationsips to
 * releases.
 *
 * @JsonapiHypermediaLinkProvider(
*    id = "hzd_react.release_comments",
 *   link_context = {
 *     "resource_object" = "node--release",
 *   }
 * )
 *
 */
final class ReleaseCommentLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

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
   * The user data service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentUser($container->get('current_user'));
    $provider->setEntityTypeManager($container->get('entity_type.manager'));
    $provider->setUserData($container->get('user.data'));

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
   * Sets the user data service.
   *
   * @param \Drupal\user\UserData $userData
   *   The user data service.
   */
  public function setUserData(UserData $userData) {
    $this->userData = $userData;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkRelationType() {
    return 'release-comments';
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof JsonApiDocumentTopLevel);

    $link_cacheability = new CacheableMetadata();
    $link_cacheability->addCacheContexts(['user.release_comments_permissions']);

    $authorized = FALSE;
    $group = Group::load(RELEASE_MANAGEMENT);
    $groupMember = $group->getMember($this->currentUser);
    if (array_intersect(['site_administrator', 'administrator'], $this->currentUser->getRoles())) {
      $authorized = TRUE;
    }
    $roles = $groupMember->getRoles();
    if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
      $authorized = TRUE;
    }
    $rw_comments_permission = $this->userData->get('cust_group', $this->currentUser->id(), 'rw_comments_permission');
    if ($rw_comments_permission) {
      $authorized = TRUE;
    }

    if ($authorized === FALSE) {
      return AccessRestrictedLink::createInaccessibleLink($link_cacheability);
    }

    // Nid of the release.
    $releaseNid = $context->getField("drupal_internal__nid")->value;

    // Use nid of the release to find associated Early Warnings.
    $releaseComments = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'release_comments')
      ->condition('field_release_ref', $releaseNid)
      ->execute();
    
    $serviceNid = $context->getField("field_relese_services")->entity->id();

    $url = Url::fromRoute('view.release_kommentare_ref.page_1',[],['query' => [
      'services' => $serviceNid,
      'releases' => $releaseNid,
    ]]);

    return AccessRestrictedLink::createLink(AccessController::groupRWCommentsAccess(RELEASE_MANAGEMENT), $link_cacheability, $url, $this->getLinkRelationType(), [
      'releaseComments' => $releaseComments,
      'releaseCommentCount' => count($releaseComments),
    ]);
  }

}
