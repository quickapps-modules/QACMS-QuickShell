<?php
App::uses('AppShell', 'Console/Command');

class ModuleTask extends AppShell {
	public $tasks = array('QuickShell.Gui');
	public $uses = array('Module');
	public $quit = false;

	public function main() {
		$this->out(__t('Quickapps CMS - Modules'));
		$this->hr();

		$this->Gui->menu(
			$this,
			array(
				array('Create new module', 'create'),
				array('List installed modules', 'listModules'),
				array('Module information', 'info'),
				array('Back', 'quit')
			)
		);

		if (!$this->quit) {
			$this->hr();
			$this->main();
		}
	}

	public function listModules($numerate = false, $type = 'module') {
		$modules = $this->Module->find('all',
			array(
				'conditions' => array(
					'Module.type' => $type
				)
			)
		);

		$i = 0;

		foreach ($modules as $module) {
			$i++;
			$prefix = $numerate ? "{$i}. " : '- ';
			$_yaml = $this->readYaml($module['Module']['name']);
			$yaml = $type == 'theme' ? $_yaml['info'] : $_yaml;
			$version = isset($yaml['version']) ? " ({$yaml['version']})" : '';
			$siteOrCore = ' [SITE]';

			if (($type == 'theme' && QuickApps::is('theme.core', $module['Module']['name'])) ||
				($type == 'module' && QuickApps::is('module.core', $module['Module']['name']))
			) {
				$siteOrCore = ' [CORE]';
			}

			$this->out("{$prefix}{$yaml['name']}{$version}{$siteOrCore}");
		}

		return $modules;
	}

	public function info($type = 'module') {
		$modules = $this->listModules(true, $type);
		$opt = $this->in(__t('Which %s ?', $type), range(1, count($modules)));
		$module = $modules[$opt-1];
		$_yaml = $this->readYaml($module['Module']['name']);
		$yaml = $type == 'theme' ? $_yaml['info'] : $_yaml;
		$version = isset($yaml['version']) ? " v{$yaml['version']}" : '';

		$this->hr();
		$this->out("{$yaml['name']}{$version}");
		$this->hr();
		$this->out(__t('Machine Name: %s', $module['Module']['name']));
		$this->out(__t('Active: %s',
			($module['Module']['status'] ? __t('Yes') : __t('No'))
		));
		$this->out(__t('Description: %s', $yaml['description']));

		if (isset($yaml['category'])) {
			$this->out(__t('Category: %s', $yaml['category']));
		}

		$this->out(__t('Core: %s', $yaml['core']));

		if (isset($yaml['dependencies'])) {
			$this->out(__t('Dependencies:'));

			foreach ($yaml['dependencies'] as $d) {
				$this->out("\t- {$d}");
			}
		}

		if (isset($_yaml['regions'])) {
			$this->out(__t('Theme regions:'));

			foreach ($_yaml['regions'] as $alias => $name) {
				$this->out("\t- {$name} ({$alias})");
			}
		}
	}

	public function create() {
		$savePath = ROOT . DS . 'webroot' . DS . 'files' . DS;

		if (!is_writable($savePath)) {
			$this->out(__t('Write permission ERROR: %s', $savePath));

			return;
		}

		$module = $this->_read();

		$this->hr();

		if ($created = $this->build($savePath, $module)) {
			$this->out(__t('Your module has been compressed and saved in: %s', $savePath . $module['alias'] . '.zip'));
		}
	}

	public function build($path, $info, $type = 'module') {
		$path = str_replace(DS . DS, DS, $path . DS);

		if (!is_writable($path)) {
			return false;
		}

		$source = dirname(dirname(dirname(__FILE__))) . DS . 'Templates' . DS . "qa_{$type}";
		$TypeName = $type == 'module' ? 'ModuleName' : 'ThemeName';
		$Folder = new Folder();

		$Folder->delete($path . $info['alias']);
		$Folder->delete($path . $info['alias'] . '.zip');

		if ($this->__rcopy($source, $path . $info['alias'])) {
			$folders = $Folder->tree(realpath($path . $info['alias']), true, 'dir');

			foreach ($folders as $folder) {
				$folderName = basename($folder);

				if (strpos($folderName, $TypeName) !== false) {
					rename($folder, dirname($folder) . DS . str_replace($TypeName, $info['alias'], $folderName));
				}
			}

			$files = $Folder->tree(realpath($path . $info['alias']), true, 'file');

			foreach ($files as $file) {
				$fileName = basename($file);

				if ($this->__file_ext($fileName) != 'yaml') {
					$this->__replace_file_content($file, "/{$TypeName}/", $info['alias']);
				}

				if ($fileName == "{$TypeName}.yaml") {
					App::uses('Spyc', 'vendors');

					$yamlContent = Spyc::YAMLDump($info['yaml']);

					file_put_contents($file, $yamlContent);
				}

				if ($fileName == 'default.ctp' && $type = 'theme' && !empty($info['yaml']['regions'])) {
					$body = '';

					foreach ($info['yaml']['regions'] as $id => $name) {
						$body .= "\n\t\t<?php //if (\$this->Block->regionCount('{$id}')): ?>";
						$body .= "\n\t\t\t<div class=\"region {$id}\">";
						$body .= "\n\t\t\t\t<h4 class=\"region-name\">" . __t('Region') .": <span>{$name}</span></h4>";
						$body .= "\n\t\t\t\t<?php echo \$this->Block->region('{$id}'); ?>";
						$body .= "\n\t\t\t</div>";
						$body .= "\n\t\t<?php //endif; ?>\n";
					}

					$this->__replace_file_content($file, "/\<\!-- REGIONS --\>/", $body);
				}

				if (strpos($fileName, $TypeName) !== false) {
					rename($file, dirname($file) . DS . str_replace($TypeName, $info['alias'], $fileName));
				}
			}

			$this->build_field(realpath($path . $info['alias']));

			App::uses('PclZip', 'vendors');

			$zip = new PclZip($path . $info['alias'] . '.zip');

			$zip->create($path . $info['alias'], PCLZIP_OPT_REMOVE_PATH, $path);
			$Folder->delete($path . $info['alias']);

			return true;
		}

		return false;
	}

	public function build_field($path) {
		$build = true;
		$path = str_replace(DS . DS, DS, $path . DS);
		$source = dirname(dirname(dirname(__FILE__))) . DS . 'Templates' . DS . "qa_field";
		$module_name = Inflector::camelize(basename($path));

		while ($build) {
			$field = array(
				'name' => false,
				'description' => false,
				'max_instances' => null
			);
			$build = strtolower($this->in(__t('Does your module has any Field ? (Y/N)'))) == 'y';

			if ($build) {
				while (!$field['name']) {
					$field['name'] = $module_name . Inflector::camelize($this->in(__t('Enter your field name in CamelCase. e.g.: "FieldName" [R]')));
				}

				while (!$field['description']) {
					$field['description'] = $this->in(__t('Enter a brief description [R]'));
				}

				while ($field['max_instances'] === null) {
					$field['max_instances'] = $this->in(__t("How many instances of this field can be attached to entities ?\nLeave empty for no limits, or zero (0) to indicate that field can not be attached to entities. [O]"));

					if (empty($field['max_instances'])) {
						$field['max_instances'] = false;
					} else {
						$field['max_instances'] = intval($field['max_instances']);
					}
				}

				$Folder = new Folder($path . 'Fields' . DS . $field['name'], true);

				if ($this->__rcopy($source, $path . 'Fields' . DS . $field['name'])) {
					App::uses('Spyc', 'vendors');

					$yamlContent = Spyc::YAMLDump($field);

					file_put_contents($path . 'Fields' . DS . $field['name'] . DS . 'FieldName.yaml', $yamlContent);

					$files = $Folder->findRecursive();

					foreach ($files as $file) {
						$file_name = basename($file);
						$file_path = dirname($file) . DS;
						$content = file_get_contents($file);
						$content = str_replace('field_name', Inflector::underscore($field['name']), $content);
						$content = str_replace('FieldName', $field['name'], $content);

						file_put_contents($file, $content);

						if (strpos($file_name, 'FieldName') !== false) {
							rename($file, $file_path . str_replace('FieldName', $field['name'], $file_name));
						}

						if (strpos($file_name, 'field_name') !== false) {
							rename($file, $file_path . str_replace('field_name', Inflector::underscore($field['name']), $file_name));
						}
					}
				}
			}
		}
	}

	protected function _read() {
		$yaml = array(
			'name' => null,
			'description' => null,
			'category' => null,
			'version' => null,
			'core' => null,
			'author' => null,
			'dependencies' => array()
		);
		$moduleAlias = null;

		while (empty($moduleAlias)) {
			$moduleAlias = Inflector::camelize($this->in(__t('Alias name of the module, in CamelCase. e.g.: "MyTestModule" [R]')));
		}

		while (empty($yaml['name'])) {
			$yaml['name'] = $this->in(__t('Human readable name of the module. e.g.: "My Test Module" [R]'));
		}

		while (empty($yaml['description'])) {
			$yaml['description'] = $this->in(__t('Brief description [R]'));
		}

		while (empty($yaml['category'])) {
			$yaml['category'] = $this->in(__t('Category [R]'));

			if (Inflector::camelize($yaml['category']) == 'Core') {
				$yaml['category'] = null;

				$this->out(__t('Invalid category name.'));
			}
		}

		while (empty($yaml['version'])) {
			$yaml['version'] = $this->in(__t('Module version. e.g.: 1.0, 2.0.1 [R]'));
		}

		while (empty($yaml['core'])) {
			$yaml['core'] = $this->in(__t('Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
		}

		$authorName = $this->in(__t('Author name [O]'));
		$authorEmail = $this->in(__t('Author email [O]'));
		$yaml['author'] = "{$authorName} <{$authorEmail}>";

		if (empty($authorName) && empty($authorEmail)) {
			unset($yaml['author'] );
		}

		$addDependencies = false;

		while (!in_array($addDependencies, array('Y', 'N'))) {
			$addDependencies = strtoupper($this->in(__t('Does your module depends of other modules ?'), array('Y', 'N')));
		}

		$yaml['dependencies'] = array();

		if ($addDependencies == 'Y') {
			$continue = true;
			$i = 1;

			while ($continue) {
				$dependency = array('name' => null, 'version' => null);

				$this->out(__t('#%s', $i));

				while (empty($dependency['name'])) {
					$dependency['name'] = Inflector::camelize($this->in(__t('Module alias')));
				}

				$dependency['version'] = trim($this->in(__t('Module version. (Optional)')));

				while (!in_array($continue, array('Y', 'N'), true)) {
					$continue = strtoupper($this->in(__t('Add other module dependency ?'), array('Y', 'N')));
				}

				$continue = ($continue == 'Y');
				$dependency['version'] = !empty($dependency['version']) ? " ({$dependency['version']})": "";
				$yaml['dependencies'][] = "{$dependency['name']}{$dependency['version']}";
				$i++;
			}
		}

		if (empty($yaml['dependencies'])) {
			unset($yaml['dependencies']);
		}

		return array(
			'alias' => $moduleAlias,
			'yaml' => $yaml
		);
	}

	public function readYaml($module) {
		App::uses('Spyc', 'vendors');

		if (strpos($module, 'Theme') === 0) {
			$module = preg_replace('/^Theme/', '', $module);
			$path = App::themePath($module) . $module . '.yaml';
		} else {
			$path = CakePlugin::path($module) . $module . '.yaml';
		}

		return Spyc::YAMLLoad($path);
	}

	private function __file_ext($fileName) {
		return strtolower(str_replace('.', '', strtolower(strrchr($fileName, '.'))));
	}

	private function __replace_file_content($file_path, $pattern, $replacement) {
		$content = file_get_contents($file_path);

		if ($content) {
			$new_content = preg_replace($pattern, $replacement, $content);

			file_put_contents($file_path, $new_content);
		}

		return false;
	}

	private function __rcopy($src, $dst) {
		$dir = opendir($src);

		@mkdir($dst);

		while(false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . DS . $file)) {
					$this->__rcopy($src . DS . $file, $dst . DS . $file);
				} else {
					if (!copy($src . DS . $file, $dst . DS . $file)) {
						return false;
					}
				}
			}
		}

		closedir($dir);

		return true;
	}

	public function quit() {
		$this->quit = true;
	}
}
