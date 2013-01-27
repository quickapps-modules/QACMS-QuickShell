#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Console
 * @since         CakePHP(tm) v 1.2.0.5012
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
$ds = DIRECTORY_SEPARATOR;
$root = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . $ds . 'QuickApps';

include(dirname(__FILE__) . $ds . 'ShellDispatcher.php');

if (!file_exists($root)) {
	// shared-core installation, look for core location

	$isWindows = $ds === '\\' ? true : false;
	$cache = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . $ds . 'tmp' . $ds . 'cache';
	$handle = opendir($cache);
	$found = false;

	while (($file = readdir($handle)) !== false) {
		if (preg_match('/(.*)modules$/', $file)) {
			$found = $file;
		}
	}

	if (!$found) {
		trigger_error('Could not locate QuickApps CMS core files.', E_USER_ERROR);
	} else {
		$content = file_get_contents($cache . $ds . $found);
		
		if ($isWindows) {
			$content = str_replace('\\\\\\\\', '\\', $content);
		}
		
		$firstLine = strpos($content, "\n");
		$content = substr($content, $firstLine + 1);
		$modules = @unserialize($content);
		
		if (!is_array($modules)) {
			trigger_error('Could not locate QuickApps CMS core files.', E_USER_ERROR);
		} else {
			foreach ($modules as $name => $module) {
				if ($name === 'System') {
					break;
				}
			}

			if ($name !== 'System') {
				trigger_error('Could not locate QuickApps CMS core files.', E_USER_ERROR);
			} else {
				$root = dirname(dirname($modules[$name]['path']));
			}
		}
	}
}

$argv[2] = $root;

define('CORE_PATH', $root . $ds);
define('QS_CONSOLE_PATH', dirname(__FILE__));
unset($paths, $path, $found, $dispatcher, $root, $ds, $firstLine, $content, $modules, $module, $name);

return ShellDispatcher::run($argv);
