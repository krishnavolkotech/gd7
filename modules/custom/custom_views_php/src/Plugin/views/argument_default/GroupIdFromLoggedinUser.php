<?php

/**
 * @file
 * Contains \Drupal\custom_views_php\Plugin\views\argument_default\GroupIdFromLoggedinUser.
 */

namespace Drupal\custom_views_php\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to extract a group ID.
 *
 * @ViewsArgumentDefault(
 *   id = "group_id_from_loggedin_user",
 *   title = @Translation("Group ID from Loggedin User")
 * )
 */
class GroupIdFromLoggedinUser extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a new GroupIdFromUrl instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    //$contexts = $context_provider->getRuntimeContexts(['group']);
    //$this->group = $contexts['group']->getContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.group_route_context')
    );
  }

    /**
   * {@inheritdoc}
   */
  protected static function groupIdsByMember($uid) {
    $contents = array(
      'closed_private-group_membership',
      'closed-group_membership',
      'downtimes-group_membership',
      'group_content_type_7b308aea24fe7',
      'group_content_type_d4b06e2b6aad0',
      'moderate-group_membership',
      'open-group_membership',
      'quick_info-group_membership',
      'group_content_type_6693a40b54133',
      'group_content_type_c26112f8ad4cd',
    );
    //$contents =
    //pr($uid);exit;
    $gpc = \Drupal::database()->select('group_content_field_data', 'g')
        ->fields('g', array('gid'))
        ->condition('entity_id', $uid,'=')
        ->condition('type', $contents, 'IN')
        ->distinct()
        ->execute()
        ->fetchCol();
    return $gpc;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $uid = \Drupal::currentUser()->id();
    
//    if (!empty($this->group) && $id = $this->group->id()) {
//      return $id;
//    }
//echo $this->groupIdsByMember($uid);exit;
    return $this->groupIdsByMember($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // We cache the result on the route instead of the URL so that path aliases
    // can all use the same cache context. If you look at ::getArgument() you'll
    // see that we actually get the group ID from the route, not the URL.
    return ['route'];
  }

}
