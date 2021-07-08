<?php

namespace Drupal\cust_filebrowser\Services;

use Drupal\filebrowser\Services\Common;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Class AltCommon
 * @package Drupal\cust_filebrowser\Services
 */
class AltCommon extends Common {

  /**
   * Returns current User object.
   * 
   * @return \Drupal\Core\Session\AccountProxy
   */
  public function getCurrentUser() {
    return \Drupal::currentUser();
  }

  /**
   * Returns a Group object for given entity.
   * 
   * @param NodeInterface $node
   * The entity that belongs to a group.
   * 
   * @return \Drupal\group\Entity\Group
   */
  public function getGroupByNode(NodeInterface $node) {
    $groupContent = GroupContent::loadByEntity($node);
    $group = reset($groupContent)->getGroup();
    return $group;
  }

  /**
   * Returns an array containing the allowed actions for logged in user.
   * Array is used to complete building the form ActionForm.php
   * @param $node
   * @var array $actions
   * array with the following keys:
   * 'operation': the form action id that this element will trigger
   * 'title': title for the form element
   * 'type': 'link' will create a link that opens in a slide-down window
   *         'button' will create a button that opens in a slide-down window
   *         'default' creates a normal submit button
   * 'needs_item': this element needs items selected on the form
   * @return array
   */
  public function userAllowedActions($node) {
    // @todo: Default Funktion implementieren. Dies hier soll es erweitern,
    // wenn Filebrowser im Gruppenkontext verwendet wird.
    $actions = [];

    /** @var \Drupal\Core\Session\AccountProxy $account */
    $account = $this->getCurrentUser();

    /** @var \Drupal\filebrowser\Filebrowser $filebrowser */
    $filebrowser = $node->filebrowser;

    // Crash, wenn benutzer abgemeldet ist
    if ($account->isAuthenticated()) {
      $group = $this->getGroupByNode($node);
    }
    else {
      return $actions;
    }
    if (!$group) {
      return $actions;
    }

    // needs_item indicates this button needs items selected on the form
    // Upload button.
    if ($filebrowser->enabled && $group->hasPermission(Common::FILE_UPLOAD, $account)) {
      $actions[] = [
        'operation' => 'upload',
        'title' =>$this->t('Upload'),
        'type' => 'link',
        'needs_item' => FALSE,
        'route' => 'filebrowser.action',
      ];
    }
    // Create folder button.
    if ($filebrowser->createFolders && $group->hasPermission(Common::CREATE_FOLDER, $account)) {
      $actions[] = [
        'operation' => 'folder',
        'title' =>$this->t('Add folder'),
        'needs_item' => FALSE,
        'type' => 'link',
      ];
    }
    // Delete items button.
    if ($group->hasPermission(Common::DELETE_FILES, $account)) {
      $actions[] = [
        'operation' => 'delete',
        'title' => $this->t('Delete'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Rename items button.
    if ($filebrowser->enabled && $group->hasPermission(Common::RENAME_FILES, $account)) {
      $actions[] = [
        'operation' => 'rename',
        'title' => $this->t('Rename items'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Edit description button.
    if ($filebrowser->enabled && $group->hasPermission(Common::EDIT_DESCRIPTION, $account)) {
      $actions[] = [
        'operation' => 'description',
        'title' => $this->t('Edit description'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // ZIP download button.
    if ($this->canDownloadArchiveModified($node, $group, $account) && function_exists('zip_open')) {
      $actions[] = [
        'operation' => 'archive',
        'title' => $this->t('Download archive'),
        'needs_item' => TRUE,
        'type' => 'default',
      ];
    }
    return $actions;
  }

  /**
   * Check if user can download ZIP archives.
   * 
   * @param NodeInterface $node 
   *  Node containing the filebrowser.
   * 
   * @param Group $group
   *  Group containing the node.
   * 
   * @param AccountProxy $account
   *  The User Account.
   * 
   * @return bool
   */
  function canDownloadArchiveModified(NodeInterface $node, Group $group, AccountProxy $account) {
    $download_archive = $node->filebrowser->downloadArchive;
    return ($node->access('view') && $download_archive && $group
      ->hasPermission(Common::DOWNLOAD_ARCHIVE, $account));
  }
}
