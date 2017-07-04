<?php

namespace Drupal\cust_group\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\cust_group\ImAttachmentInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Contact entity.
 *
 * @ingroup cust_group
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "cust_group_imattachments_data",
 *   label = @Translation("ImAttachments Data"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\content_entity_example\Entity\Controller\ContactListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {},
 *     "access" = "Drupal\content_entity_example\ContactAccessControlHandler",
 *   },
 *   base_table = "im_attachments_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {},
 * )
 *
 */


class ImAttachmentsData extends ContentEntityBase implements ImAttachmentInterface {

    /**
     * {@inheritdoc}
     *
     * When a new entity instance is added, set the user_id entity reference to
     * the current user as the creator of the instance.
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
      parent::preCreate($storage_controller, $values);
      $values += array(
        'user_id' => \Drupal::currentUser()->id(),
      );
    }
    /**
     * {@inheritdoc}
     */
    public function getCreatedTime() {
      return $this->get('created')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedTime() {
      return $this->get('changed')->value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setChangedTime($timestamp) {
      $this->set('changed', $timestamp);
      return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getChangedTimeAcrossTranslations()  {
      $changed = $this->getUntranslated()->getChangedTime();
      foreach ($this->getTranslationLanguages(FALSE) as $language)    {
        $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
        $changed = max($translation_changed, $changed);
      }
      return $changed;
    }
  
    /**
     * {@inheritdoc}
     */
    public function getOwner() {
      return $this->get('user_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerId() {
      return $this->get('user_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwnerId($uid) {
      $this->set('user_id', $uid);
      return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(UserInterface $account) {
      $this->set('user_id', $account->id());
      return $this;
    }
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
       $fields['id'] = BaseFieldDefinition::create('integer')
         ->setLabel(t('ID'))
         ->setDescription(t('The ID of the Imattachemntdata entity.'))
         ->setReadOnly(TRUE);
       $fields['nid'] = BaseFieldDefinition::create('integer')
         ->setLabel(t('NodeID'))
         ->setDescription(t('The NodeID of the Imattachemntdata entity.'))
         ->setReadOnly(TRUE);
       $fields['fid'] = BaseFieldDefinition::create('integer')
         ->setLabel(t('FileID'))
         ->setDescription(t('The FileID of the attachment.'))
         ->setReadOnly(TRUE);
       $fields['description'] = BaseFieldDefinition::create('text_long')
         ->setLabel(t('Description'))
         ->setSettings(array(
           'default_value' => '',
           'text_processing' => 0,
         ))
         ->setDisplayOptions('form', array(
           'type' => 'text_textarea',
           'settings' => array(
             'rows' => 4,
           ),
         ));
       $fields['ticket_id'] = BaseFieldDefinition::create('string')
         ->setLabel(t('TicketId'))
         ->setDescription(t('The ticket id.'))
         ->setSettings(array(
           'max_length' => 255,
           'text_processing' => 0,
         ))
         ->setDefaultValue(NULL)
         ->setDisplayOptions('form', array(
           'type' => 'string_textfield',
           'weight' => -6,
         ));
       $fields['langcode'] = BaseFieldDefinition::create('language')
         ->setLabel(t('Language code'))
         ->setDescription(t('The language code of Contact entity.'));
       $fields['created'] = BaseFieldDefinition::create('created')
         ->setLabel(t('Created'))
         ->setDescription(t('The time that the entity was created.'));

       $fields['changed'] = BaseFieldDefinition::create('changed')
         ->setLabel(t('Changed'))
         ->setDescription(t('The time that the entity was last edited.'));

       return $fields;
    }

}