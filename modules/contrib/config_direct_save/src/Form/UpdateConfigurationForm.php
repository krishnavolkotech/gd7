<?php

namespace Drupal\config_direct_save\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the settings form for updating configurations.
 *
 * @package Drupal\config_direct_save\Form
 */
class UpdateConfigurationForm extends FormBase {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Gets the config factory.
   */
  public function __construct(StorageInterface $target_storage, ConfigManagerInterface $config_manager) {
    $this->targetStorage = $target_storage;
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_update_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['config_directory'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Config source'),
      '#description' => $this->t('Select config source directory'),
      '#options' => array_flip(['sync' => Settings::get('config_sync_directory')]),
    ];
    $form['backup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Backup'),
      '#description' => $this->t('Check to make a backup for a specific config source(sync for example.)'),
    ];
    $form['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update configuration'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create config files.
    $this->createConfigFiles($form, $form_state);
  }

  /**
   * Override the old configurations.
   */
  public function createConfigFiles(array &$form, FormStateInterface $form_state) {
    // Get the name of config source( sync, text, etc...).
    $config_directory_selected = $form_state->getValue('config_directory');
    $config_files_names = $this->configManager->getConfigFactory()->listAll();
    // If the user check to make a backup, a directory will be created.
    if ($form_state->getValue('backup')) {
      $current_date = date('d-m-Y-H-i-s');
      $folder_backup = $config_directory_selected . "-" . $current_date;
      $this->recurseCopy($config_directory_selected, $folder_backup);
    }
    // Delete old configurations files( except .htaccess).
    $this->unlinkRecursive($config_directory_selected, 'yml');
    foreach ($config_files_names as $name) {
      // Data associated to file.
      $data = Yaml::encode($this->configManager->getConfigFactory()
        ->get($name)
        ->getRawData());
      // Create new files.
      file_put_contents($config_directory_selected . "/$name.yml", $data);
    }
    // Get all override data from the remaining collections.
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $target = str_replace('.', '/', $collection);
      $this->unlinkRecursive($config_directory_selected . "/" . $target . "/", 'yml');
      if (!is_dir($config_directory_selected . "/" . $target . "/")) {
        mkdir(($config_directory_selected . "/" . $target . "/"), 0775, TRUE);
      }
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $target = str_replace('.', '/', $collection);
        file_put_contents($config_directory_selected . "/" . $target . "/$name.yml", Yaml::encode($collection_storage->read($name)));
      }
    }
    $this->messenger()->addMessage($this->t("The configuration has been uploaded."));
  }

  /**
   * Copy the directory of.
   */
  public function recurseCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (FALSE !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        if (is_dir($src . '/' . $file)) {
          $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
        }
        else {
          copy($src . '/' . $file, $dst . '/' . $file);
        }
      }
    }
    closedir($dir);
  }

  /**
   * Delete all configurations.
   */
  public function unlinkRecursive($dir_name, $ext) {
    // Exit if there's no such directory.
    if (!file_exists($dir_name)) {
      return FALSE;
    }
    // Open the target directory.
    $dir_handle = dir($dir_name);
    // Take entries in the directory one at a time.
    while (FALSE !== ($entry = $dir_handle->read())) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      $abs_name = "$dir_name/$entry";

      if (is_file($abs_name) && preg_match("/^.+\.$ext$/", $entry)) {
        if (unlink($abs_name)) {
          continue;
        }
        return FALSE;
      }
      // Recurse on the children if the current entry
      // happens to be a "directory".
      if (is_dir($abs_name) || is_link($abs_name)) {
        $this->unlinkRecursive($abs_name, $ext);
      }
    }
    $dir_handle->close();
    return TRUE;
  }

}
