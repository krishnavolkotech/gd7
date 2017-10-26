<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupContentRejectForm.
 */

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a group content entity.
 */
class GroupContentRejectForm extends ConfirmFormBase {
  
  
  public function __construct(RouteMatchInterface $routeMatch) {
    $this->entity = $routeMatch->getParameter('group_content');
  }
  
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }
  
  /**
   * @return string
   */
  public function getFormId(){
    return "reject_membership_request";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reject %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    // @todo Read a redirect from the plugin?
    $entity = $this->entity;
    return new Url('view.hzd_group_members.pending',['group'=>$entity->getGroup()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reject');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();

    $grouplabel = $this->entity->get('gid')->referencedEntities()[0]->label();
    $subject = t("Membership Request for a Group - @groupLabel has been rejected", ['@groupLabel' => $grouplabel]);
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'cust_group';
    $key = 'immediate_notifications';
    $params['subject'] = $subject;
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $send = TRUE;
    $user = $this->entity->get('entity_id')->referencedEntities()[0];
    $params['message'] = t('Dear @user,<br><br>Membership Request for Group @groupLabel has been rejected.', [
        '@user' => $user->getDisplayName(),
        '@groupLabel' => $grouplabel
    ]);
    $result = $mailManager->mail($module, $key, $user->getEmail(), $langcode, $params, NULL, $send);
    if ($result['result']) {
      drupal_set_message(t('Mail sent.'), 'status');
    }
    
    \Drupal::logger('group_content')->notice('@type: rejected %title.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]);
    // @todo Read a redirect from the plugin?
    $form_state->setRedirect('view.hzd_group_members.pending',['group'=>$entity->getGroup()->id()]);
  }

}
