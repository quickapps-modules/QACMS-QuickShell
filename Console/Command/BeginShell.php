<?php
App::uses('ConnectionManager', 'Model');
App::uses('Model', 'Model');

class BeginShell extends AppShell {
	public $tasks = array(
		'QuickShell.Gui',
		'QuickShell.Module',
		'QuickShell.Theme',
		'QuickShell.Utility'
	);
	public $quit = false;

	public function main() {
		$this->out(__d('quick_shell', 'Quickapps CMS - Shell'));
		$this->hr();
		$this->Gui->menu(
			$this,
			array(
				array('Module shell', 'Module->main'),
				array('Themes shell', 'Theme->main'),
				array('Utility shell', 'Utility->main'),
				array('Quit', 'quit')
			)
		);

		if (!$this->quit) {
			$this->hr();
			$this->main();
		}
	}

/**
 * Allows to perform a database backup.
 *
 * ### Usage
 *
 *     ./cake QuickShell.begin db_backup -t users,blocks -d ./my_backups/ -s -m
 *
 * This will backup the tables `users` and `blocks`, and DB schema.
 * All will be stored on `/my_backups/{DATE @ HOUR @ TIMESTAMP}`.
 */
	public function db_backup() {
		$table = isset($this->args[0]) ? $this->args[0] : null;
		$this->Utility->export_data($this->params);
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->addSubcommand('db_backup', array(
			'help' => __d('quick_shell', 'Backup all the data of your database.'),
			'parser' => array(
				'options' => array(
					'table' => array('short' => 't', 'help' => __d('quick_shell', 'Comma separated list of tables to backup. Leave empty to perform a full backup.'), 'required' => false, 'default' => '*'),
					'destination' => array('short' => 'd', 'help' => __d('quick_shell', 'Destination folder where to save your backup. by default: %s', ROOT . DS . 'tmp' . DS . 'cache'  . DS . 'db_backups'), 'required' => false),
					'schema' => array('short' => 's', 'help' => __d('quick_shell', 'Backup db schema.'), 'required' => false, 'boolean' => true),
					'timemark' => array('short' => 'm', 'help' => __d('quick_shell', 'Stores backup files on a timestamp folder inside "destination".'), 'required' => false, 'boolean' => true)
				)
			)
		));

		return $parser;
	}

	public function quit() {
		$this->quit = true;
	}
}