<?php

namespace Drupal\grequest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group membership request route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupMembershipRequestController extends ControllerBase {

  /**
   * Membership request manager.
   *
   * @var Drupal\grequest\MembershipRequestManager
   */
  protected $membershipRequestManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MembershipRequestManager $membership_request_manager) {
    $this->membershipRequestManager = $membership_request_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grequest.membership_request_manager')
    );
  }

  /**
   * Provides the form for request a group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which a membership request will be submitted.
   *
   * @return array
   *   A group request membership form.
   */
  public function requestMembership(GroupInterface $group) {
    $group_content = $this->membershipRequestManager->create($group);
    return $this->entityFormBuilder()->getForm($group_content, 'group-request-membership');
  }

  /**
   * The _title_callback for the request membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to request membership of.
   *
   * @return string
   *   The page title.
   */
  public function requestMembershipTitle(GroupInterface $group) {
    return $this->t('Request membership group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for approval a group membership.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content.
   *
   * @return array
   *   A group approval membership form.
   */
  public function approveMembership(GroupContentInterface $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-approve-membership');
  }

  /**
   * The _title_callback for the approval requested membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   *
   * @return string
   *   The page title.
   */
  public function approveMembershipTitle(GroupInterface $group) {
    return $this->t('Approve membership request for group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for rejection a group membership.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content.
   *
   * @return array
   *   A group rejection membership form.
   */
  public function rejectMembership(GroupContentInterface $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-reject-membership');
  }

  /**
   * The _title_callback for the rejection requested membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   *
   * @return string
   *   The page title.
   */
  public function rejectMembershipTitle(GroupInterface $group) {
    return $this->t('Reject membership request for group %label', ['%label' => $group->label()]);
  }

}
