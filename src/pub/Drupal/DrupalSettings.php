<?php

/**
 * @file
 * Generate Drupal Settings.php for PRs.
 */

namespace pub\Drupal;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use pub\ProjectConfig;


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
  public function generateSettings($pr_number) {
    // $config = new Config();
    $project_config = new ProjectConfig();
    $fs = new Filesystem();

    $short_name = 'site_PR_' . $pr_number;
    $file_name = $pr_number . '-settings.inc';

    // TODO: Make this path come from a local config setting.
    $path = '/var/www/site-php/' . $project_config['shortname'] . '/' . $file_name;

    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }

    $output = "
<?php

  \$base_url = 'http://{$pr_number}.pr.publisher7.com';

  \$databases['default'] = array ('default' =>
    array (
      'database' => 'pr_{$short_name}',
      'username' => '',
      'password' => '',
      'host' => '127.0.0.1',
      'port' => '',
      'driver' => 'mysql',
      'prefix' => '',
    ),
  );

  // Set the program name for syslog.module.
  \$conf['syslog_identity'] = 'pr-{$short_name}';

  // Set up memcache settings.
  \$conf['memcache_key_prefix'] = '{$short_name}_';
  \$conf['memcache_servers'] = array(
    '127.0.0.1:11211' => 'default'',
  );

  // Imagemagick path to convert binary setting.
  \$conf['imagemagick_convert'] = '/usr/bin/convert';";

    try {
      $fs->dumpFile($path, $output);
    }
    catch (IOExceptionInterface $e) {
      echo "An error occurred while creating settings.inc file at " . $e->getPath();
    }
  }
}
