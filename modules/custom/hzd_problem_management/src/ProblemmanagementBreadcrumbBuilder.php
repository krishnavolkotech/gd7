<?php

namespace Drupal\mymodule;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;

/**
 *
 */
class ProblemmanagementBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    if ($attributes['_route'] == 'node_page') {
      return $attributes['node']->bundle() == 'news';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb[] = $this->l($this->t('Home'), NULL);
    // $breadcrumb[] = $this->l($this->t('News'), 'news');.
    return $breadcrumb;
  }

}
