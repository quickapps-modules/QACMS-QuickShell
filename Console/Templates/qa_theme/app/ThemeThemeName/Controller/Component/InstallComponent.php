<?php
/**
 * Install class for an individual Module.
 *
 */
class InstallComponent extends Component {
/**
 * An instance of InstallerComponent object
 * This object contains all the information about the module being installed
 * and several utility methods.
 *
 * @var InstallerComponent
 */
	public $Installer;

/**
 * Called before the module installation process. A boolean FALSE return will
 * stop the installation.
 *
 * @return boolean FALSE to stop the installation.
 */
	public function beforeInstall() {
		return true;
	}

/**
 * Called after the module has been installed.
 *
 * @return void
 */
	function afterInstall() {
	}

/**
 * Called before the module uninstall process. A boolean FALSE return will
 * stop the uninstallation.
 *
 * @return boolean FALSE to stop the installation.
 */
	public function beforeUninstall() {
		return true;
	}

/**
 * Called after the module has been uninstalled.
 *
 * @return void
 */
	public function afterUninstall() {
	}
}