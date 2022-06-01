<?php
namespace Drupal\cust_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Returns responses for NSM Users routes.
 */
class RevisionSkipConfirm extends ControllerBase {
    
  /**
   * The node revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $revision;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new NodeRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $node_storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->nodeStorage = $node_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function skipConfirm() {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $current_path = \Drupal::service('path.current')->getPath();
    $arr_ids = explode('/', $current_path);

    if(isset($arr_ids[4])) {
        
        $this->revision = $this->nodeStorage->loadRevision($arr_ids[4]);

        $original_revision_timestamp = $this->revision->getRevisionCreationTime();

        $this->revision = $this->prepareRevertedRevision($this->revision);
        $this->revision->revision_log = $this->t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
        $this->revision->setRevisionUserId($this->currentUser()->id());
        $this->revision->setRevisionCreationTime($this->time->getRequestTime());
        $this->revision->setChangedTime($this->time->getRequestTime());
        $this->revision->save();

        $this->messenger()
          ->addStatus($this->t('@type %title has been reverted to the revision from %revision-date.', [
            '@type' => node_get_type_label($this->revision),
            '%title' => $this->revision->label(),
            '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
          ]));
        $url = \Drupal\Core\Url::fromRoute('entity.node.version_history')->setRouteParameters(['node' => $this->revision->id()]); 
        return new RedirectResponse($url->toString()); 

    }
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\node\NodeInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(NodeInterface $revision) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }
  
}
