<?php

namespace Drupal\custom_views_php\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
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
 * @ViewsField("userstatus")
 */
class UserStatus extends FieldPluginBase
{
    
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
      if($values->users_field_data_cust_profile_uid) {
        $rowUser = User::load($values->users_field_data_cust_profile_uid);
        if(hzd_user_inactive_status_check($rowUser->id())) {
          return t("Inactive");
        }else if($rowUser->isBlocked()) {
          return t("Blocked");
        }else if ($rowUser->isActive()) {
          return t("Active");
        }
      }
      return t('Not Found');
    }
    
}
