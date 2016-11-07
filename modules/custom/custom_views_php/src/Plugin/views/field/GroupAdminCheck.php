<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\Component\Utility\Html;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handler
 *
 * @ViewsField("group_admin_check")
 */
class GroupAdminCheck extends FieldPluginBase {

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
//    $this->ensureMyTable();
//    $this->query->addOrderBy(NULL,
//      'group_admin_check',
//      'desc'
//    );
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
    $type = $values->_entity->getGroup()->bundle();
    $user = $values->_entity->get('entity_id')->referencedEntities()[0];
    $roles = $values->_entity->getGroup()->getMember($user)->getRoles();
    $value = 0;
    if(in_array($type.'-admin',array_keys($roles))){
      if(!$value){
        $value = 1;
      }
    }
    return $value;
  }

}
