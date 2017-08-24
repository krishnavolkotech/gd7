<?php

namespace Drupal\inactive_user\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Simple filter to handle matching of boolean values.
 *
 * This handler checks to see if a string field is Null or not.
 * It is otherwise identical to the parent operator.
 *
 * Definition items:
 * - label: (REQUIRED) The label for the checkbox.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("is_null")
 */
class IsNullOperator extends BooleanOperator {

  public function query() {
    $this->ensureMyTable();
    $where = "$this->tableAlias.$this->realField ";

    if (empty($this->value)) {
//      $where .= "= ''";
//      if ($this->accept_null) {
        $where = "( $this->tableAlias.$this->realField IS NULL)";
//      }
    }
    else {
      $where = "( $this->tableAlias.$this->realField IS NOT NULL)";
    }
    $this->query->addWhereExpression($this->options['group'], $where);
  }

}
