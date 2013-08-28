<?php
App::uses('AppShell', 'Console/Command');

class GuiTask extends AppShell {
	public function menu($obj, $options, $in_msg = null) {
		$i = 0;
		$mappedOpts = array();

		foreach ($options as $option) {
			$i++;

			if (preg_match('/\[[a-z]{1}\]/i', $option[0], $letter_opt)) {
				$symbol = str_replace(array('[', ']'), '', trim($letter_opt[0]));
				$mappedOpts[strtoupper($symbol)] = $i-1;

				$this->out($option[0]);
			} else {
				$this->out($i . '. ' . $option[0]);
			}
		}

		$in_msg = empty($in_msg) ? __d('quick_shell', 'What would you like to do') : $in_msg;
		$allowed = range(1, $i);

		if (!empty($mappedOpts)) {
			foreach ($mappedOpts as $symbol => $n) {
			   $allowed[$n] = $symbol;
			}
		}

		$do = $this->in($in_msg, $allowed);

		if (isset($options[$do-1][1]) ||
			in_array(strtoupper($do), array_keys($mappedOpts))
		) {
			if (in_array(strtoupper($do), array_keys($mappedOpts))) {
				$action = $options[$mappedOpts[strtoupper($do)]];
			} else {
				$action = $options[$do-1];
			}

			if (strpos($action[1], '->') !== false) {
				$explode = Hash::filter(explode('->', $action[1]));
				$method = array_pop($explode);
				$_object =& $obj;

				foreach ($explode as $o) {
					$_object =& $_object->{$o};
				}

				$_object->{$method}();
			} else {
				$obj->{$action[1]}();
			}
		} else {
			$this->out(__d('quick_shell', 'You have made an invalid selection.'));
		}
	}
}