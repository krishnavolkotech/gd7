<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handler
 *
 * @ViewsField("risk_cluster_risks")
 */
class Risks extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['hide_alter_empty'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $build = [];
    $cluster = $values->_entity;
    $risks = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['field_risk_clusters' => $cluster->getEntity()->id(), 'type' => 'risk']);
    $items = array_map(function ($risk) {
        return $risk->toLink($risk->get('field_id')->value . '-' . $risk->get('title')->value)->toString();
    }, $risks);
    $build = [
      '#items' => $items,
      '#theme' => 'item_list',
      '#type' => 'ul'
    ];
    return $build;
  }

}
