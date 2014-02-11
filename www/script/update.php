<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

use Pi\Application\Installer\Module as ModuleInstaller;

/**
 * Pi Engine update script to update a module that can not be executed from normal admin
 *
 * Usage guide
 * 1. Edit `var/config/engine.php` (or `var/config/custom/engine.php` is specified), set:
 *      `'environment' => 'close'`
 * 2. Execute the script `//pi.tld/script/update.php` to update system module
 * 3. Restore engine config:
 *      `'environment' => ''`
 */

// Pi boot with no engine bootup: current file is located in www/script/...
require __DIR__ . '/../boot.php';

// Only allowed under maintenance state
if ('close' !== Pi::config('environment')) {
    if (substr(PHP_SAPI, 0, 3) == 'cgi') {
        header('Status: 403 Forbidden');
    } else {
        header('HTTP/1.1 403 Forbidden');
    }

    echo 'Access denied!';

    return;
}

// Get module and verify
$module = 'system';
if (!empty($_SERVER['QUERY_STRING'])) {
    $module = $_SERVER['QUERY_STRING'];
}
if (empty($module) || !Pi::service('module')->isActive($module)) {
    if (substr(PHP_SAPI, 0, 3) == 'cgi') {
        header('Status: 404 Not Found');
    } else {
        header('HTTP/1.1 404 Not Found');
    }

    echo 'Request not found!';

    return;
}

$row = Pi::model('module')->find($module, 'name');
$installer = new ModuleInstaller;
$result = $installer->update($row);
//$details = $installer->getResult();

if ($result) {
    Pi::service('asset')->remove('module/' . $module);
    Pi::service('asset')->publishModule($module);
    $message = sprintf('Module %s update succeeded.', $module);
} else {
    $message = sprintf('Module %s update failed.', $module);
}

if (substr(PHP_SAPI, 0, 3) == 'cgi') {
    header('Status: 200');
} else {
    header('HTTP/1.1 200');
}

echo $message;

return;