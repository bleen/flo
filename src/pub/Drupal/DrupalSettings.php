<?php

/**
 * @file
 * Generate Drupal Settings.php for PRs.
 */

namespace pub\Drupal;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use pub\ProjectConfig;
use pub\Config;

class DrupalSettings {

  const EXPORT_PATH = '/var/www/site-php/subscription/{{PR-123}}.settings.php';

  /**
   * Generate Settings PHP for a specific PR.
   *
   * @param int $pr_number
   *   Pull Request Number used to build the settings.php file.
   *
   * @throws \Exception
   */
  static public function generateSettings($pr_number) {
    $config = new Config();
    $pub_config = $config->load();
    $project_config = new ProjectConfig();
    $project_config->load();
    $fs = new Filesystem();

    $path = $project_config->settings['pull_request']['prefix'] . '-' . $pr_number . $project_config->settings['pull_request']['domain'];
    $url = "http://{$path}";
    $local_site_path = $pub_config['pr-directories'] . $path;

    //TODO: Fix PR environment for EVERYONE!
    $local_settings_php = $local_site_path . '/docroot/sites/install/settings.local.php';

    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }
    $output = "
<?php

  \$base_url = '{$url}';

  \$databases['default'] = array ('default' =>
    array (
      'database' => '{$project_config->settings['pull_request']['prefix']}_{$pr_number}',
      'username' => 'root',
      'password' => '',
      'host' => '127.0.0.1',
      'port' => '',
      'driver' => 'mysql',
      'prefix' => '',
    ),
  );

  // Set the program name for syslog.module.
  \$conf['syslog_identity'] = '{$project_config->settings['pull_request']['prefix']}_{$pr_number}';

  // Set up memcache settings.
  \$conf['memcache_key_prefix'] = '{$project_config->settings['pull_request']['prefix']}_{$pr_number}_';
  \$conf['memcache_servers'] = array(
    '127.0.0.1:11211' => 'default'',
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
