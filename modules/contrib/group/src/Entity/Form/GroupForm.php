<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Form controller for the group edit forms.
 *
 * @ingroup group
 */
class GroupForm extends ContentEntityForm {
  
  
  public function form(array $form, FormStateInterface $form_state) {
    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];
    return parent::form($form, $form_state);
  }
  
  /**
   * Entity builder updating the node status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\node\GroupInterface $group
   *   The node updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\node\NodeForm::form()
   */
  function updateStatus($entity_type_id, GroupInterface $group, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $group->setPublished($element['#published_status']);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // We call the parent function first so the entity is saved. We can then
    // read out its ID and redirect to the canonical route.
    $return = parent::save($form, $form_state);

    // Display success message.
    $t_args = [
      '@type' => $this->entity->getGroupType()->label(),
      '%title' => $this->entity->label(),
    ];
    drupal_set_message(t('@type %title has been created.', $t_args));

    $form_state->setRedirect('entity.group.canonical', ['group' => $this->entity->id()]);
    return $return;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $group = $this->entity;
    if (\Drupal::currentUser()->hasPermission('administer group') || $group->hasPermission('administer group',\Drupal::currentUser())) {
      // isNew | prev status » default   & publish label             & unpublish label
      // 1     | 1           » publish   & Save and publish          & Save as unpublished
      // 1     | 0           » unpublish & Save and publish          & Save as unpublished
      // 0     | 1           » publish   & Save and keep published   & Save and unpublish
      // 0     | 0           » unpublish & Save and keep unpublished & Save and publish
      
      // Add a "Publish" button.
      $element['publish'] = $element['submit'];
      // If the "Publish" button is clicked, we want to update the status to "published".
      $element['publish']['#published_status'] = TRUE;
      $element['publish']['#dropbutton'] = 'save';
      if ($group->isNew()) {
        $element['publish']['#value'] = t('Save and publish');
      }
      else {
        $element['publish']['#value'] = $group->isPublished() ? t('Save and keep published') : t('Save and publish');
      }
      $element['publish']['#weight'] = 0;
      
      // Add a "Unpublish" button.
      $element['unpublish'] = $element['submit'];
      // If the "Unpublish" button is clicked, we want to update the status to "unpublished".
      $element['unpublish']['#published_status'] = FALSE;
      $element['unpublish']['#dropbutton'] = 'save';
      if ($group->isNew()) {
        $element['unpublish']['#value'] = t('Save as unpublished');
      }
      else {
        $element['unpublish']['#value'] = !$group->isPublished() ? t('Save and keep unpublished') : t('Save and unpublish');
      }
      $element['unpublish']['#weight'] = 10;
      
      // If already published, the 'publish' button is primary.
      if ($group->isPublished()) {
        unset($element['unpublish']['#button_type']);
      }
      // Otherwise, the 'unpublish' button is primary and should come first.
      else {
        unset($element['publish']['#button_type']);
        $element['unpublish']['#weight'] = -10;
      }
      
      // Remove the "Save" button.
      $element['submit']['#access'] = FALSE;
    }
    
    $element['delete']['#access'] = $group->access('delete');
    $element['delete']['#weight'] = 100;
    
    return $element;
  }
  
}
