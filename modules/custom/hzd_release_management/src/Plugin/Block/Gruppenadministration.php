<?php

namespace Drupal\hzd_release_management\Plugin\Block;

use Drupal\cust_group\Controller\CustNodeController;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "hzd_group_admin",
 *   admin_label = @Translation("HZD Gruppenadministration"),
 *   category = @Translation("Blocks")
 * )
 */
class Gruppenadministration extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->hzdGroupAdminLinks();
  }

  /**
   *
   */
  protected function blockAccess(AccountInterface $account) {
    if (\Drupal::currentUser()->id()) {
      $group = \Drupal::routeMatch()->getParameter('group');
      if (is_object($group)) {
        $groupId = $group->id();
      }
      else {
        $groupId = $group;
      }
      if (CustNodeController::isGroupAdmin($groupId)) {
        return AccessResult::allowed();
      }
      return AccessResult::neutral();
    }
    else {
      return AccessResult::neutral();
    }
  }

  /**
   *
   */
  public function hzdGroupAdminLinks() {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $groupId = $group->id();
    }
    else {
      $groupId = $group;
    }
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Inhaltsübersicht'),'view.group_content.page_1',['arg_0'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Inhalt erstellen'),'entity.group_content.group_node.create_page',['group'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Benutzer'),'view.group_members.page_1',['arg_0'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Störungen und Blockzeiten'),'downtimes.DowntimessettingForm',['group'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Bekannte Fehler und Probleme'),'problem_management.problem_settings',['group'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Releases'),'hzd_release_management.release_settings',['group'=>$groupId]);
    $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Mass Contact'),'mass_contact.bulk_mail_group_members_form');
    if ($groupId == 32) {
      $menuItems[] = \Drupal\Core\Link::createFromRoute(t('Planungsdateien'),'hzd_release_management.display_planning_files',['group'=>$groupId]);
    }
    
/*    $menuHtml = '<ul class="menu nav">
    <li><a href="/group/' . $groupId . '/content">Contents</a></li>
    <li><a href="/group/' . $groupId . '/node/create">Content</a></li>
    <li><a href="/group/' . $groupId . '/approved-members">Users</a></li>
    <li><a href="/group/' . $groupId . '/downtime_settings">Disturbances and block times</a></li>
    <li><a href="/group/' . $groupId . '/problem_settings">Known Issues</a></li>
    <li><a href="/group/' . $groupId . '/release_settings">Releases</a></li>
    <li><a href="/admin/group/mass_contact">Mass Contact</a></li>';
    if ($groupId == 32) {
      $menuHtml .= '<li><a href="/group/' . $groupId . '/planning-files">Planning Files</a></li>';
    }
    $menuHtml .= '</ul>';*/
    $menuHtml = [
                 '#items'=>$menuItems,
                 '#theme'=>'item_list',
                 '#list_type'=>'ul',
                 '#attributes'=>['class'=>['menu nav']],
                 '#cache'=>['max-age'=>0]
                 ];
    return $menuHtml;
  }

}
