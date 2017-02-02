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

    \Drupal::logger('group_content')->notice('@type: rejected %title.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]);
    // @todo Read a redirect from the plugin?
    $form_state->setRedirect('view.hzd_group_members.pending',['group'=>$entity->getGroup()->id()]);
  }

}
