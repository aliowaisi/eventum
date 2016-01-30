#!/usr/bin/php
<?php
/**
 * Tool for helping Eventum upgrades.
 *
 * See our Wiki for documentation:
 * https://github.com/eventum/eventum/wiki/Upgrading
 */

use Eventum\Db\Migrate;

define('INSTALL_PATH', __DIR__ . '/..');
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid init.php redirecting us to setup if not configured yet
$setup_path = CONFIG_PATH . '/setup.php';
if (!file_exists($setup_path) || !filesize($setup_path) || !is_readable($setup_path)) {
    error_log("ERROR: Can't get setup.php in '" . CONFIG_PATH . "'");
    error_log('Did you forgot to copy config from old install? Is file readable?');
    exit(1);
}

require_once INSTALL_PATH . '/init.php';

// see if certain patch is needed to be run
$patch = isset($argv[1]) ? (int)$argv[1] : null;

try {
    $dbmigrate = new Migrate(INSTALL_PATH . '/upgrade');
    $dbmigrate->patch_database($patch);
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
    exit(1);
}
