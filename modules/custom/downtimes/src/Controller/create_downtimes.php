<?php
/**
 * @file
 * Contains \Drupal\problem_management\Controller\ProblemsController
 */

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;

/**
 * Class CurrentProblemsController
 * @package Drupal\problem_management\Controller
 */
class ProblemsController extends ControllerBase {

	function downtimes_access($string) {
	  global $user;
	  if (isset($_SESSION['Group_name'])) {
	    if ($_SESSION['Group_name'] == INCEDENT_MANAGEMENT_GROUP) {
	      return TRUE;
	    }
	    else {
	      return FALSE;
	    }
	  }
	  if ($user->uid != 0) {
	    return TRUE;
	  }
	  switch ($string) {
	    case 'master group':
	      if (isset($_SESSION['Group_name'])) {
		if ($_SESSION['Group_name'] == INCEDENT_MANAGEMENT_GROUP) {
		  return TRUE;
		}
		else {
		  return FALSE;
		}
	      }
	      break;
	    case 'view downtimes':
	    case 'view':
	      if (is_numeric(arg(1))) {
		$node = node_load(arg(1));
		if ($node->type == DOWNTIMES) {
		  return TRUE;
		}
		else {
		  return FALSE;
		}
	      }
	      return TRUE;
	  }
	  return FALSE;
	}
}
