<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
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
    $item_list = [];
    $cluster = $values->_entity;
    $riskItems = \Drupal::entityQuery('node')
      ->condition('field_risk_clusters',$cluster->getEntity()->id())
      ->condition('type','risk')
      ->sort('created','desc')
      ->execute();
    if(count($riskItems) > 0) {
      $risk_title = node_get_title_fast($riskItems);
      $risk_ids = node_get_field_data_fast($riskItems, 'field_risk_id');
      $item_list = array_map(function ($key, $title, $field_id) {
        $options = ['absolute' => TRUE];
        $url = Url::fromRoute('entity.node.canonical', ['node' => $key], $options);
        return Link::fromTextAndUrl($field_id . '-' . $title, $url);
      }, array_keys($risk_title), $risk_title, $risk_ids);
    }
    $build = [
      '#items' => $item_list,
      '#theme' => 'item_list',
      '#type' => 'ul'
    ];
    return $build;
  }

}
