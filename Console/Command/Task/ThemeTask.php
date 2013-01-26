<?php
App::uses('AppShell', 'Console/Command');

class ThemeTask extends AppShell {
	public $uses = array('Variable');
	public $tasks = array('QuickShell.Module', 'QuickShell.Gui');
	public $quit = false;

	public function main() {
		$this->out(__t('Quickapps CMS - Themes'));
		$this->hr();
		$this->Gui->menu(
			$this,
			array(
				array('Create new theme', 'create'),
				array('List installed themes', 'listModules'),
				array('Theme information', 'info'),
				array('Back', 'quit')
			)
		);

		if (!$this->quit) {
			$this->hr();
			$this->main();
		}
	}

	public function listModules($numerate = false) {
		$this->Module->listModules($numerate, 'theme');
	}

	public function info() {
		$this->Module->info('theme');
	}

	public function create() {
		$savePath = ROOT . DS . 'webroot' . DS . 'files' . DS;

		if (!is_writable($savePath)) {
			$this->out(__t('Write permission ERROR: %s', $savePath));

			return;
		}

		$theme = $this->_read();

		$this->hr();

		if ($created = $this->Module->build($savePath, $theme, 'theme')) {
			$this->out(__t('Your theme has been compressed and saved in: %s', $savePath . $theme['alias'] . '.zip'));
		}
	}

	protected function _read() {
		$yaml = array(
			'info' => array(
				'admin' => false,
				'name' => null,
				'description' => null,
				'version' => null,
				'core' => null,
				'author' => null
			),
			'stylesheets' => array(
				'all' => array('reset.css', 'styles.css')
			),
			'regions' => array(),
			'layout' => 'default',
			'login_layout' => 'login'
		);
		$themeAlias = null;
		$yaml['info']['admin'] = strtoupper($this->in(__t('Is your theme an admin theme ?'), array('Y', 'N')));
		$yaml['info']['admin'] = ($yaml['info']['admin'] == 'Y');

		while (empty($themeAlias)) {
			$themeAlias = Inflector::camelize($this->in(__t('Alias name of the theme, in CamelCase. e.g.: "MyTestTheme" [R]')));
		}

		while (empty($yaml['info']['name'])) {
			$yaml['info']['name'] = $this->in(__t('Human readable name of the theme. e.g.: "My Test Theme" [R]'));
		}

		while (empty($yaml['info']['description'])) {
			$yaml['info']['description'] = $this->in(__t('Brief description [R]'));
		}

		$yaml['info']['version'] = $this->in(__t('Theme version. e.g.: 1.0, 2.0.1 [O]'));

		if (empty($yaml['info']['version'])) {
			unset($yaml['info']['version']);
		}

		while (empty($yaml['info']['core'])) {
			$yaml['info']['core'] = $this->in(__t('Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
		}

		while (empty($yaml['info']['description'])) {
			$yaml['info']['core'] = $this->in(__t('Required version of Quickapps CMS. e.g: 1.x, >=1.0 [R]'));
		}

		$authorName = $this->in(__t('Author name [O]'));
		$authorEmail = $this->in(__t('Author email [O]'));
		$yaml['info']['author'] = "{$authorName} <{$authorEmail}>";

		if (empty($authorName) && empty($authorEmail)) {
			unset($yaml['info']['author']);
		}

		$addDependencies = false;
		$addDependencies = strtoupper($this->in(__t('Does your theme depends of some modules ?'), array('Y', 'N')));
		$yaml['info']['dependencies'] = array();

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
				$yaml['info']['dependencies'][] = "{$dependency['name']}{$dependency['version']}";
				$i++;
			}
		}

		if (empty($yaml['info']['dependencies'])) {
			unset($yaml['info']['dependencies']);
		}

		$this->nl();
		$this->hr();
		$this->out(__t('Adding theme regions'));
		$this->hr();

		$importFrom = strtoupper($this->in(__t('Do you want to add regions present in the actual theme?'), array('Y', 'N')));

		if ($importFrom == 'Y') {
			$t = $yaml['info']['admin'] ? 'admin' : 'site';
			$defaultTheme = $this->Variable->find('first',
				array(
					'conditions' => array(
						'name' => "{$t}_theme"
					)
				)
			);
			$__yaml = $this->Module->readYaml('Theme'.unserialize($defaultTheme['Variable']['value']), 'theme');
			$n = array();
			$i = 0;

			foreach ($__yaml['regions'] as $a => $r) {
				$n[$i] = array($a, $r);
				$i++;

				$this->out("{$i}. {$r} ({$a})");
			}

			if ($i) {
				$opts = range(1, $i);
				$import = $this->in(__t('Type in regions separated by comma `,`.'), $opts);
				$import = preg_replace('/[^0-9,]*/', '', trim($import));
				$import = explode(',', $import);
				$import = Hash::filter($import);

				foreach ($import as $i) {
					if (isset($n[$i-1])) {
						$yaml['regions'][$n[$i-1][0]] = $n[$i-1][1];
					}
				}
			}
		}

		if ($importFrom == 'Y') {
			$addRegion = strtoupper($this->in(__t('Add more regions?'), array('Y', 'N')));
			$addRegion = ($addRegion == 'Y');
		} else {
			$addRegion = true;
		}

		while ($addRegion) {
			$region = $this->in(__t('Region name:'));

			if (!empty($region)) {
				$yaml['regions'][strtolower(Inflector::slug($region, '-'))] = $region;
			}

			$addRegion = strtoupper($this->in(__t('Add other region'), array('Y', 'N')));
			$addRegion = ($addRegion == 'Y');
		}

		if ($yaml['info']['admin']) {
			$regions = array();

			if (!in_array('toolbar', array_keys($yaml['info']['regions']))) {
				$regions['toolbar'] = 'Toolbar';
			}

			if (!in_array('help', array_keys($yaml['info']['regions']))) {
				$regions['help'] = 'Help';
			}

			$regions = array_merge($regions, $yaml['regions']);
			$yaml['regions'] = $regions;
		} else {
			unset($yaml['login_layout']);
		}

		return array(
			'alias' => $themeAlias,
			'yaml' => $yaml
		);
	}

	public function quit() {
		$this->quit = true;
	}
}
