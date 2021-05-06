<?php

namespace Drupal\cust_filebrowser\Services;

use Drupal\filebrowser\Services\Common;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Group;
use Drupal\node\NodeInterface;
use \Drupal\Core\Session\AccountProxy;
/**
 * Override to set CSV filename when exported.
 */
class AltCommon extends Common {

  // Permissions
  // const DELETE_FILES = 'delete files';
  // const EDIT_DESCRIPTION = 'edit description';
  // const CREATE_FOLDER = 'create folders';
  // const FILE_UPLOAD = 'upload files';
  // const RENAME_FILES = 'rename files';
  // const DOWNLOAD_ARCHIVE = 'download archive';
  // const DOWNLOAD = 'download files';

  // const CREATE_LISTING = 'create listings';
  // const DELETE_OWN_LISTINGS = 'delete listings';
  // const DELETE_ANY_LISTINGS = 'delete any listings';
  // const EDIT_OWN_LISTINGS = 'edit own listings';
  // const EDIT_ANY_LISTINGS = 'edit any listings';
  // const VIEW_LISTINGS = 'view listings';


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
    // @todo: Default Funktion implementieren. Dies hier soll es erweitern, wenn Filebrowser im Gruppenkontext verwendet wird.
    /** @var \Drupal\filebrowser\Filebrowser $filebrowser */

    $actions = [];
    $account = \Drupal::currentUser();
    $filebrowser = $node->filebrowser;

    // Crash, wenn benutzer abgemeldet ist
    if ($account->isAuthenticated()) {
      // @todo Richtige Gruppe laden.
      $group = Group::load(2);
      // $group = reset(GroupContent::loadByEntity($node))->getGroup();
    }
    else {
      return $actions;
    }
    if (!$group) {
      return $actions;
    }

    // needs_item indicates this button needs items selected on the form
    // Upload button
    if ($filebrowser->enabled && $group->hasPermission(Common::FILE_UPLOAD, $account)) {
      $actions[] = [
        'operation' => 'upload',
        'title' =>$this->t('Upload'),
        'type' => 'link',
        'needs_item' => FALSE,
        'route' => 'filebrowser.action',
      ];
    }
    //Create folder
    if ($filebrowser->createFolders && $group->hasPermission(Common::CREATE_FOLDER, $account)) {
      $actions[] = [
        'operation' => 'folder',
        'title' =>$this->t('Add folder'),
        'needs_item' => FALSE,
        'type' => 'link',
      ];
    }
    // Delete items button
    if ($group->hasPermission(Common::DELETE_FILES, $account)) {
      $actions[] = [
        'operation' => 'delete',
        'title' => $this->t('Delete'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Rename items button
    if ($filebrowser->enabled && $group->hasPermission(Common::RENAME_FILES, $account)) {
      $actions[] = [
        'operation' => 'rename',
        'title' => $this->t('Rename items'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
    // Edit description button
    if ($filebrowser->enabled && $group->hasPermission(Common::EDIT_DESCRIPTION, $account)) {
      $actions[] = [
        'operation' => 'description',
        'title' => $this->t('Edit description'),
        'needs_item' => TRUE,
        'type' => 'button',
      ];
    }
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
   * @param NodeInterface $node Node containing the filebrowser
   * @return bool
   */
  function canDownloadArchiveModified(NodeInterface $node, Group $group, AccountProxy $account) {
    $download_archive = $node->filebrowser->downloadArchive;
    return ($node->access('view') && $download_archive && $group
        ->hasPermission(Common::DOWNLOAD_ARCHIVE, $account));
  }
}
