<?php

/**
 * @file
 * Contains \Drupal\cust_address_book\Controller\HzdAddressBook.
 *
 */

namespace Drupal\cust_address_book\Controller;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

define('DEFAULT_DISPLAY_ROWS', 20);

/**
 * Class HzdAddressBook
 * @package Drupal\cust_address_book\Controller
 */
class HzdAddressBook extends ControllerBase {

  function address_book() {
    /*$breadcrumb = array();
    $breadcrumb[] = l(t('Home'), NULL);
    if(isset($_SESSION['Group_name'])) {
      $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/'.$_SESSION['Group_id']);
    } 
    $breadcrumb[] = t("Address Book");
    drupal_set_breadcrumb($breadcrumb);*/
    //TODO: Breadcrumb
    $limit = \Drupal::request()->get('no_rows');
    $limit = $limit ? $limit : DEFAULT_DISPLAY_ROWS;
    if($_SESSION['Group_name']) {
      $total_count = db_query('SELECT COUNT(*) as count FROM {group_content_field_data} gcfd, {users_field_data} u 
                     WHERE u.uid = gcfd.entity_id AND gcfd.gid = :gid AND u.status = 1 AND u.uid <> 0', 
                     array(":gid" => $_SESSION['Group_id']))->fetchField();
      $output[]['#attached']['library']['drupalSettings']['Group_id'] = $_SESSION['Group_id'];
    }
    else {
      $total_count = db_query('SELECT COUNT(*) as count FROM {cust_profile} cp, {users_field_data} u WHERE u.uid = cp.uid and u.uid <> 0 
                     and u.status = 1')->fetchField();
    }
    $output[]  = array('#markup' => '<div class="addressbook_totalcount">'. $total_count ." ". t('members total') .'</div>');
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\cust_address_book\Form\AddressBookFilterForm', $limit);
    $output[] = $this->_get_users_list();
    //drupal_add_js('misc/jquery.form.js');
    //drupal_add_js(drupal_get_path('module', 'downtimes') . '/jquery.tablesorter.min.js');
    //drupal_add_js(drupal_get_path('module', 'cust_address_book') . '/address_book.js');
    return $output;
  }

  function address_book_user_view($username) {
    $user = \Drupal\user\Entity\User::load($username);
    $page = \Drupal::entityManager()->getViewBuilder($user->getEntityTypeId())->view($user);
    return $page;
  }

  // get all users list
  function _get_users_list() {
    //global $user;
    /*$address = explode('/',$_REQUEST['q']);
    if ($_REQUEST['q'] == 'mitglieder' || $address[1] == 'gruppenmitglieder') {
      unset($_SESSION['address_book_rows']);
      unset($_SESSION['address_book_state']);
      unset($_SESSION['address_book_name']);
      unset($_SESSION['address_book_search']);
    }*/
    
    isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'DESC'? $ord = "ASC": $ord = "DESC";
    if (\Drupal::request()->get('no_rows')) {
      $_SESSION['address_book_rows'] = \Drupal::request()->get('no_rows');
    }
    if (\Drupal::request()->get('state')) {
      $_SESSION['address_book_state'] = \Drupal::request()->get('state');
    }
    if (\Drupal::request()->get('name_st')) {
      $_SESSION['address_book_name'] = \Drupal::request()->get('name_st');
    }
    if (\Drupal::request()->get('add_search')) {
      $_SESSION['address_book_search'] = \Drupal::request()->get('add_search');
    }
    $limit = $_SESSION['address_book_rows'] ? $_SESSION['address_book_rows'] : DEFAULT_DISPLAY_ROWS;

    $query = db_select('cust_profile', 'cp');
    $query->join('users_field_data', 'u', 'u.uid = cp.uid');
    $query->join('states', 's', 'cp.state_id = s.id');
    $query->condition('u.uid', 0, '<>')
          ->condition('u.status', 0, '<>');
    if(isset($_SESSION['Group_name'])) {
      $query->join('group_content_field_data', 'gcfd', 'gcfd.entity_id = cp.uid');
      $query->condition('gcfd.gid', $_SESSION['Group_id'], '=');
    }
    // there is a record in the state table for select with id 1.
    // so ignoring the state id 1
    if($_SESSION['address_book_state'] && $_SESSION['address_book_state'] > 1) {
      $query->condition('cp.state_id', $_SESSION['address_book_state'], '=');
    }
    if(isset($_SESSION['address_book_name']) && $_SESSION['address_book_name'] != 'All') {
      $query->where("UCASE(cp.lastname) like '".$_SESSION['address_book_name']."%%' ");
    }
    if(isset($_SESSION['address_book_search']) && $_SESSION['address_book_search'] != t(ADDRESS_BOOK_SEARCH_DEFAULT)) {
      $sf = strtoupper($_SESSION['address_book_search']);
      $query->where("(UCASE(cp.firstname) like '%".$sf."%' or UCASE(cp.lastname) like '%".$sf."%' or UCASE(u.mail) like '%".$sf."%')");
    }

    $count_query = clone $query;
    $count_query->addExpression('COUNT(u.uid)');
    $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $paged_query->setCountQuery($count_query);

    $paged_query->fields('cp', array('uid', 'firstname', 'lastname', 'phone', 'position', 'state_id'))
                ->fields('u', array('mail', 'name'));
    $paged_query->addField('s', 'abbr', 'state');
    $paged_query->orderBy('cp.lastname', 'ASC');
    if($limit != 'all') {
      $page_limit = ($limit ? $limit : 20);
      $paged_query->limit($page_limit);
      $result = $paged_query->execute()->fetchAll();
    }
    else {
      $result = $query->execute()->fetchAll();
    }

    foreach($result as $user_info) {
      if (strlen($user_info->position) > 17) {
        $user_info->position = substr($user_info->position, 0, 16)."...";
      }
      $row = array($user_info->lastname, $user_info->firstname, $user_info->position, $user_info->phone, $user_info->state_id, $user_info->mail);
      $rows[] = $row;
    }
    if ($rows) { 
      $header = array(t('Last Name'), t('First Name'), t('Position'), t('Phone'), t('State'), t('Email'));
      $output['address'] = array(
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => $header,
        '#attributes' => ['id' => "sortable", 'class' =>"tablesorter"], 
        '#empty' => t('No records found'),
      );

      $output['pager'] = array(
        '#type' => 'pager',
        '#quantity' => 5,
        '#prefix' => '<div id="pagination">',
        '#suffix' => '</div>',
      );
      $output[] = \Drupal::formBuilder()->getForm('Drupal\cust_address_book\Form\AddressBookRowsForm');
      return $output;
    }    
    else {
      $output['#markup'] = t('results not found');
      return $output;
    }
  }

}
