<?php

namespace Drupal\menu_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Configure Menu Export settings.
 */
class MenuImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_import_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'menu_export.settings',
      'menu_export.export_data'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import Menu Links'),
    );
    return $form;
  }

  /**
   * Custom form validation for email.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $menus = $this->config('menu_export.export_data')->get();
    foreach ($menus as $key => $menu) {
      if(!Menu::load($menu)){
        echo "menu not found";exit;
      }
      foreach ($menu as $uuid => $linkDataSerialized) {
        $menuLinkEntity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $uuid);
        if(!$menuLinkEntity){
          $menuLinkEntity = MenuLinkContent::create();
        }
        $linkData = unserialize($linkDataSerialized);
        foreach ($linkData as $key => $items) {
          $menuLinkEntity->set($key, $items);
        }
        $menuLinkEntity->save();
        unset($menuLinkEntity);
      }
    }

    drupal_set_message('Menu Items Imported successfully', 'status');
    parent::submitForm($form, $form_state);
  }

}
