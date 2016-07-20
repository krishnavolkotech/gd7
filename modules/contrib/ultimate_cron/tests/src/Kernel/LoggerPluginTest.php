<?php
/**
 * @file
 * Contains \Drupal\Tests\ultimate_cron\Kernel\LoggerPluginTest.php
 */

namespace Drupal\Tests\ultimate_cron\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ultimate_cron\Entity\CronJob;
use Drupal\ultimate_cron\Plugin\ultimate_cron\Logger\CacheLogger;
use Drupal\ultimate_cron\Plugin\ultimate_cron\Logger\DatabaseLogger;

/**
 * Tests the default scheduler plugins.
 *
 * @group ultimate_cron
 */
class LoggerPluginTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('ultimate_cron', 'ultimate_cron_logger_test', 'system');

  /**
   * Tests that scheduler plugins are discovered correctly.
   */
  function testDiscovery() {
    /* @var \Drupal\Core\Plugin\DefaultPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.ultimate_cron.logger');

    $plugins = $manager->getDefinitions();
    $this->assertEquals(count($plugins), 2);

    $cache = $manager->createInstance('cache');
    $this->assertTrue($cache instanceof CacheLogger);
    $this->assertEquals($cache->getPluginId(), 'cache');

    $database = $manager->createInstance('database');
    $this->assertTrue($database instanceof DatabaseLogger);
    $this->assertEquals($database->getPluginId(), 'database');
  }

  /**
   * Tests log cleanup of the database logger.
   */
  function testCleanup() {

    $this->installSchema('ultimate_cron', ['ultimate_cron_log', 'ultimate_cron_lock']);

    \Drupal::service('ultimate_cron.discovery')->discoverCronJobs();

    $job = CronJob::load('ultimate_cron_logger_test_cron');
    $job->setConfiguration('logger', [
      'retain' => 10,
    ]);
    $job->save();

    // Run the job 12 times.
    for ($i = 0; $i < 12; $i++) {
      $job->getPlugin('launcher')->launch($job);
    }

    // There are 12 run log entries and one from the modified job.
    $log_entries = $job->getLogEntries(ULTIMATE_CRON_LOG_TYPE_ALL, 15);
    $this->assertEquals(13, count($log_entries));

    // Run cleanup.
    ultimate_cron_cron();

    // There should be exactly 10 log entries now.
    $log_entries = $job->getLogEntries(ULTIMATE_CRON_LOG_TYPE_ALL, 15);
    $this->assertEquals(10, count($log_entries));

    // Switch to expire-based cleanup.
    $job->setConfiguration('logger', [
      'expire' => 60,
      'method' => DatabaseLogger::CLEANUP_METHOD_EXPIRE,
    ]);
    $job->save();

    $ids = array_slice(array_keys($log_entries), 5);

    // Date back 5 log entries.
    db_update('ultimate_cron_log')
      ->expression('start_time', 'start_time - 65')
      ->condition('lid', $ids, 'IN')
      ->execute();

    // Run cleanup.
    ultimate_cron_cron();

    // There should be exactly 6 log entries now, as saving caused another
    // modified entry to be saved.
    $log_entries = $job->getLogEntries(ULTIMATE_CRON_LOG_TYPE_ALL, 15);
    $this->assertEquals(6, count($log_entries));

  }
}
