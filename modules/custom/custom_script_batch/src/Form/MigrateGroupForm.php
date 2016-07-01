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
    $connection->truncate('group__field_group_body')->execute();
    //$connection->commit();
  }
  
    /**
   * {@inheritdoc}
   */
  protected function groupContentCleanUp() {
    $connection = Database::getConnection();
    $connection->truncate('group_downtimes_view')->execute();
    $connection->truncate('group_problems_view')->execute();
    $connection->truncate('group_releases_view')->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_file = $this->getDefaultFile();

    $form['batch'] = array(
      '#type' => 'select',
      '#title' => 'Choose batch operation to perform',
      '#options' => array(
        'import_group' => t('Import groups'),
        'add_members' => t('Add group Members'),
        'add_group_content' => t('Add Group Content'),
      ),
    );

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
        // Gather our form value.
    $value = $form_state->getValues()['batch'];
    $form_values = $form_state->getValues();
    $uri = $form_values['fileops_file'];
    if (fopen($uri, "r")) {
      $handle = fopen($uri, "r");
      $count = 0;
      while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $count++;
        if ($count == 1) {
          continue;
        }
        $filedata[] = $data;
      }
      
    // Set the batch, using convenience methods.
    switch ($value) {
      case 'import_group':
//         $this->groupCleanUp();
//         $this->groupContentCleanUp();
//        $batch = $this->create_groups($filedata);
        break;
      
      case 'add_members':
        $batch = $this->add_members($filedata);
        break;
      case 'add_group_content':
        //$batch = $this->add_group_content($filedata);
        break;
    }

      batch_set($batch);
    }


    if (empty($uri) or ! is_file($uri)) {
      drupal_set_message(t('The file "%uri" does not exist', array('%uri' => $uri)), 'error');
      return;
    }
  }

/**
 * 
 * @param type $filedata
 * @return type
 */
  public function create_groups($filedata) {
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

  /**
 * 
 * @param type $filedata
 * @return type
 */
  public function add_members($filedata) {
    $num_operations = count($filedata);
    drupal_set_message(t('Creating an array of @num operations', array('@num' => $num_operations)));

    $operations = array();
    foreach ($filedata as $key => $row) {
      $operations[] = array(
        'add_members_batch',
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

/**
 * 
 * @param type $filedata
 * @return type
 */
  public function add_group_content($filedata) {
    $num_operations = count($filedata);
    drupal_set_message(t('Creating an array of @num operations', array('@num' => $num_operations)));

    $operations = array();
    foreach ($filedata as $key => $row) {
      $operations[] = array(
        'add_group_content_batch_op_1',
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
