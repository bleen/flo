<?php

/**
 * @file
 * Generate Drupal Settings.php for PRs.
 */

namespace pub\Drupal;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use pub\Configuration;

/**
 * Class DrupalSettings
 * @package pub\Drupal
 */
class DrupalSettings {

  const EXPORT_PATH = '/var/www/site-php/subscription/{{PR-123}}.settings.php';

  /**
   * Generate Settings PHP for a specific PR.
   *
   * @param int $pr_number
   *   Pull Request Number used to build the settings.php file.
   * @param string $site_dir
   *   Optional site directory to place settings.local.php in.
   *
   * @throws \Exception
   */
  static public function generateSettings($pr_number, $site_dir = 'default') {
    $configuration = new Configuration();
    $config = $configuration->getConfig();

    $fs = new Filesystem();

    $path = $config['pull_request']['prefix'] . '-' . $pr_number . '.' . $config['pull_request']['domain'];
    $url = "http://{$path}";
    $local_site_path = $config['pr-directories'] . $path;

    $local_settings_php = $local_site_path . "/docroot/sites/{$site_dir}/settings.local.php";

    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }
    $output = "<?php

  \$base_url = '{$url}';

  \$databases['default'] = array ('default' =>
    array (
      'database' => '{$config['pull_request']['prefix']}_{$pr_number}',
      'username' => 'root',
      'password' => '',
      'host' => '127.0.0.1',
      'port' => '',
      'driver' => 'mysql',
      'prefix' => '',
    ),
  );

  // Set the program name for syslog.module.
  \$conf['syslog_identity'] = '{$config['pull_request']['prefix']}_{$pr_number}';

  // Set up memcache settings.
  \$conf['memcache_key_prefix'] = '{$config['pull_request']['prefix']}_{$pr_number}_';
  \$conf['memcache_servers'] = array(
    '127.0.0.1:11211' => 'default',
  );

  // Imagemagick path to convert binary setting.
  \$conf['imagemagick_convert'] = '/usr/bin/convert';";

    try {
      $fs->dumpFile($local_settings_php, $output);
    }
    catch (IOExceptionInterface $e) {
      echo "An error occurred while creating settings.inc file at " . $e->getPath();
    }
  }
}
