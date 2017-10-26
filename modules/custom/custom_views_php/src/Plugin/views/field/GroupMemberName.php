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
 * @ViewsField("group_member_name")
 */
class GroupMemberName extends FieldPluginBase
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
        $type = $values->_entity->getGroup()->bundle();
        $user = $values->_entity->get('entity_id')->referencedEntities()[0];
        if ($user) {
            $getGroupMember = $values->_entity->getGroup()->getMember($user);
            $suffix = null;
            if ($getGroupMember) {
                $roles = $getGroupMember->getRoles();
                if (in_array($type . '-admin', array_keys($roles))) {
                    if (is_null($suffix)) {
                        $suffix = ' (admin)';
                    }
                }
                //AS per requirement we are adding only lastname here to append it with admin for administrators
                $db = \Drupal::database();
                $result = $db->select('cust_profile', 'cp')
                    ->fields('cp', array('lastname'))
                    ->condition('cp.uid', $user->id());
                $val = $result->execute()->fetchField();
                return $this->t($values->_entity->getEntity()->toLink($val)->toString() . $suffix);
            }
        }
        return '';
    }
    
}
