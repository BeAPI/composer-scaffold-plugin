<?php namespace BEA\Composer\ScaffoldPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class ScaffoldPlugin implements PluginInterface, Capable, CommandProvider {

	public function activate( Composer $composer, IOInterface $io ) {
	}

	public function deactivate( Composer $composer, IOInterface $io ) {
	}

	public function uninstall( Composer $composer, IOInterface $io ) {
	}

	public function getCapabilities() {
		return [
			CommandProvider::class => __CLASS__,
		];
	}

	public function getCommands() {
		return [
			new Command\ScaffoldPluginCommand,
		];
	}
}