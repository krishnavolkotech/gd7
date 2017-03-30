<?php

namespace Drupal\group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides group membership route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupMembershipController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  
  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;
  
  /**
   * Constructs a new GroupMembershipController.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(AccountInterface $current_user, EntityFormBuilderInterface $entity_form_builder) {
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provides the form for joining a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form.
   */
  public function join(GroupInterface $group) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership with the current user.
    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $this->currentUser->id(),
      'request_status' => 1,
    ]);

    return $this->entityFormBuilder->getForm($group_content, 'group-join');
  }

  /**
   * Provides the Request membership form for joining a group access check.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form access.
   */
  public function access(GroupInterface $group) {
    $currentUser = \Drupal::currentUser();
    $groupMember = $group->getMember($currentUser);
    if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1)
    ) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * Provides the Request membership form for joining a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form.
   */
  public function requestMembership(GroupInterface $group) {
    $currentUser = \Drupal::currentUser();
    $groupMember = $group->getMember($currentUser);
    if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 0)
    ) {
      return ['#markup' => $this->t('Your request for membership group of %label is in queue, Please wait for approval.', ['%label' => $group->label()])];
    }
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin('group_membership');
      // Pre-populate a group membership with the current user.
      $group_content = GroupContent::create([
            'type' => $plugin->getContentTypeConfigId(),
            'gid' => $group->id(),
            'entity_id' => $this->currentUser->id(),
            'request_status' => 0,
      ]);
      return $this->entityFormBuilder()->getForm($group_content, 'group-request');
    }
  }

  /**
   * The _title_callback for the request membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function requestMembershipTitle(GroupInterface $group) {
    return $this->t('Request membership group %label', ['%label' => $group->label()]);
  }

  /**
   * The _title_callback for the request membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function cancelMembershipTitle(GroupInterface $group) {
    return $this->t('Cancel membership request for group %label', ['%label' => $group->label()]);
  }

  /**
   * The _title_callback for the join form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function joinTitle(GroupInterface $group) {
    return $this->t('Join group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for leaving a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to leave.
   *
   * @return array
   *   A group leave form.
   */
  public function leave(GroupInterface $group) {
    $group_content = $group->getMember($this->currentUser)->getGroupContent();
    return $this->entityFormBuilder->getForm($group_content, 'group-leave');
  }

}
