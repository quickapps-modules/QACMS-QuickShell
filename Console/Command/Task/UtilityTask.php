<?php
App::uses('AppShell', 'Console/Command');

class UtilityTask extends AppShell {
	public $tasks = array('QuickShell.Gui');
	public $quit = false;

	public function main() {
		$this->out(__d('quick_shell', 'Quickapps CMS - Utilities'));
		$this->hr();
		$this->Gui->menu(
			$this,
			array(
				array('Clear cache', 'clearCache'),
				array('Password hash', 'password_hash'),
				array('Export DB data', 'export_data'),
				array('Back', 'quit')
			)
		);

		if (!$this->quit) {
			$this->hr();
			$this->main();
		}
	}

	public function clearCache() {
		$paths = array(
			ROOT . DS . 'tmp' . DS . 'cache' . DS,
			ROOT . DS . 'tmp' . DS . 'cache' . DS . 'models' . DS,
			ROOT . DS . 'tmp' . DS . 'cache' . DS . 'persistent' . DS
		);

		foreach ($paths as $path) {
			$folder = new Folder($path);
			$contents = $folder->read();
			$files = $contents[1];

			foreach ($files as $file) {
				$this->out($path . $file);
				if (@unlink($path . $file)) {
					$this->out(__d('quick_shell', 'File removed: %s', $path . $file), 1, Shell::VERBOSE);
				} else {
					$this->out(__d('quick_shell', '<error>Error:</error> %s', $path . $file), 1, Shell::VERBOSE);
				}
			}
		}
	}

	public function password_hash() {
		App::uses('Security', 'Utility');

		$in = $this->in(__d('quick_shell', 'Type in your password'));
		$salt = $this->in(__d('quick_shell', 'Type in your security salt, or leave empty to use site salt.'));
		$salt = empty($salt) ? true : $salt;
		$out = __d('quick_shell', 'Hash for "%s": %s', $in, Security::hash($in, null, $salt));

		$this->out("\n\t" . str_repeat('-', strlen($out)) . "\n\t<info>" . $out . "</info>\n\t" . str_repeat('-', strlen($out)) . "\n");
	}

/**
 * Prepares data in Config/Schema/data/ for the install script.
 * if no table_name is given all tables will be processed.
 *
 */
	public function export_data($options = array()) {
		$connection = 'default';
		$tables = array();
		$options = array_merge(
			array(
				'table' => null,
				'schema' => false,
				'destination' => ROOT . DS . 'tmp' . DS . 'cache'  . DS . 'db_backup' . DS,
				'datemark' => false
			), $options
		);
		$options['destination'] = str_replace(DS . DS, DS, $options['destination'] . DS);

		if ($options['datemark']) {
			$options['destination'] .= date(__d('quick_shell', 'Y-m-d @ H.i.s')) . ' @ ' . time() . DS;
		}

		if ($options['schema']) {
			App::uses('CakeSchema', 'Model');

			$Schema = new CakeSchema(array('path' => str_replace(DS . DS, '', $options['destination'] . DS), 'connection' => $connection));
			$content = $Schema->read(array('models' => false, 'name' => 'SchemaBackup'));

			if (!file_exists($options['destination'])) {
				$Folder = new Folder($options['destination']);

				$Folder->create($options['destination']);
			}

			if ($Schema->write($content)) {
				$this->out(__d('quick_shell', 'Schema file: %s generated', $options['destination'] . 'SchemaBackup.php'));
			}
		}

		if (empty($options['table'])) {
			$options['table'] = $this->in(__d('quick_shell', 'Type in the table name you want to export (no prefix). Leave empty to export all.'));
		}

		if (empty($options['table']) || $options['table'] === '*') {
			$tables = $this->__getAllTables($connection);
		} else {
			$options['table'] = explode(',', $options['table']);
			$options['table'] = array_map('trim', $options['table']);
			$tables = $options['table'];
		}

		foreach ($tables as $table) {
			$records = array();
			$modelAlias = 'Qa' . Inflector::classify($table);
			$model = new Model(array('name' => $modelAlias, 'table' => $table, 'ds' => $connection));
			$records = $model->find('all', array('recursive' => -1));
			$recordString = '';

			foreach ($records as $record) {
				$values = array();

				foreach ($record[$modelAlias] as $field => $value) {
					$value = str_replace("'", "\'", $value);
					$values[] = "\t\t\t'{$field}' => '{$value}'";
				}

				$recordString .= "\t\tarray(\n";
				$recordString .= implode(",\n", $values);
				$recordString .= "\n\t\t),\n";
			}

			$content = "<?php\n";
				$content .= "class " . $modelAlias . " {\n";
					$content .= "\tpublic \$table = '" . $table . "';\n";
					$content .= "\tpublic \$records = array(\n";
						$content .= $modelAlias != 'User' ? $recordString : '';
					$content .= "\t);\n\n";
				$content .= "}\n";

			App::uses('File', 'Utility');

			$filePath = $options['destination'] . 'data' . DS . $modelAlias . '.php';
			$file = new File($filePath, true);

			$file->write($content);
			$this->out('File created: ' . $filePath);
		}
	}

	public function quit() {
		$this->quit = true;
	}

/**
 * Get an Array of all the tables in the supplied connection
 * will halt the script if no tables are found.
 *
 * @param string $useDbConfig Connection name to scan.
 * @return array Array of tables in the database.
 */
	private function __getAllTables($useDbConfig = null) {
		if (!isset($useDbConfig)) {
			$useDbConfig = $this->connection;
		}

		$tables = array();
		$db = ConnectionManager::getDataSource($useDbConfig);
		$db->cacheSources = false;
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];

		if ($usePrefix) {
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}

		if (empty($tables)) {
			$this->err(__d('cake_console', 'Your database does not have any tables.'));
			$this->_stop();
		}

		return $tables;
	}
}