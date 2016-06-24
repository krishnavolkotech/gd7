<?php

namespace Drupal\custom_script_batch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form with examples on how to use cache.
 */
class MigrateGroupForm extends FormBase {

  public function __construct(
  StateInterface $state, FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager, ModuleHandlerInterface $module_handler, RequestStack $request_stack
  ) {
    $this->state = $state;
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->sessionSchemeEnabled = $this->moduleHandler->moduleExists('custom_script_batch');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $state = $container->get('state');
    $file_system = $container->get('file_system');
    $module_handler = $container->get('module_handler');
    $request_stack = $container->get('request_stack');
    $stream_wrapper_manager = $container->get('stream_wrapper_manager');
    return new static($state, $file_system, $stream_wrapper_manager, $module_handler, $request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_script_batch_form';
  }

  /**
   * Get the default file.
   *
   * This appears in the first block of the form.
   *
   * @return string
   *   The URI of the default file.
   */
  protected function getDefaultFile() {
    $fall_back_value = 'public://group.csv';
    $default_file = $this->state->get('file_example_default_file', $fall_back_value);
    return $default_file;
  }


  /**
   * {@inheritdoc}
   */
  protected function groupCleanUp() {
    $connection = Database::getConnection();
    $connection->truncate('groups')->execute();
    $connection->truncate('groups_field_data')->execute();
    $connection->truncate('group_content')->execute();
    $connection->truncate('group_content_field_data')->execute();
    $connection->truncate('group__field_description')->execute();
    $connection->truncate('group__field_old_reference')->execute();
    //$connection->commit();

  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_file = $this->getDefaultFile();

    $form['fileops_file'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_file,
      '#title' => $this->t('Enter the URI of a file'),      
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      //'#submit' => array('::handleFileRead'),
    );

    return $form;
  }

 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = array();
    $filedata = array();
    $form_values = $form_state->getValues();
    $uri = $form_values['fileops_file'];
    if (fopen($uri, "r")) {
      $this->groupCleanUp();
      $handle = fopen($uri, "r");
      $count = 0;
      while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $count++;
        if ($count == 1) {
          continue;
        }
        $filedata[] = $data;
      }
      
      $batch = $this->generateBatch1($filedata);
      batch_set($batch);
    }


    if (empty($uri) or ! is_file($uri)) {
      drupal_set_message(t('The file "%uri" does not exist', array('%uri' => $uri)), 'error');
      return;
    }

    
  }

  /**
   * Generate Batch 1.
   *
   * Batch 1 will process one item at a time.
   *
   * This creates an operations array defining what batch 1 should do, including
   * what it should do when it's finished. In this case, each operation is the
   * same and by chance even has the same $nid to operate on, but we could have
   * a mix of different types of operations in the operations array.
   */
  public function generateBatch1($filedata) {
    $num_operations = count($filedata);
    drupal_set_message(t('Creating an array of @num operations', array('@num' => $num_operations)));

    $operations = array();
    foreach ($filedata as $key => $row) {
      $operations[] = array(
        'create_group_batch_op_1',
        array(
          $row,
          $key,
          t('(Operation @operation)', array('@operation' => $key)),
        ),
      );
    }
    $batch = array(
      'title' => t('Creating an array of @num operations', array('@num' => $num_operations)),
      'operations' => $operations,
      'finished' => 'custom_script_batch_finished',
    );
    return $batch;
  }

}
