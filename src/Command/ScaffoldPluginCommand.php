<?php namespace BEA\Composer\ScaffoldPlugin\Command;

use Composer\Command\BaseCommand;
use Composer\Package\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScaffoldPluginCommand extends BaseCommand {

	/**
	 * @var array Additionnal components list.
	 */
	protected $available_components = array(
		'controller',
		'cron',
		'model',
		'route',
		'widget',
		'shortcode',
	);

	protected function configure() {
		$this->setName( 'scaffold-plugin' )->setDescription( 'Bootstrap a new WordPress plugin using Be API\'s boilerplate.' )->addArgument( 'folder', InputArgument::REQUIRED, "Your plugin's folder name" )->addArgument( 'components', InputArgument::IS_ARRAY, "Optional components you want to include in your plugin.\n Available components are :\n\t- Controller\n\t- Cron\n\t- Model\n\t- Route\n\t- Widget\n\t- Shortcode" );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$io         = new SymfonyStyle( $input, $output );
		$composer   = $this->getComposer();
		$pluginName = $input->getArgument( 'folder' );
		$components = $input->getArgument( 'components' );

		$io->block( [
			'',
			'WordPress plugin generator',
			'',
		], null, 'bg=blue;fg=white' );

		$io->writeln( [
				'',
				"Scaffolding plugin: <info>$pluginName</info>",
				'',
			] );

		// Get plugin components
		if ( ! empty( $components ) ) {
			$io->writeln( 'You have selected those components for your plugin :' . \join( ', ', \array_map( function ( $i ) {
						return "<comment>$i</comment>";
					}, $components ) ) );

			if ( false === $io->confirm( "Is that Ok for you ? ", true ) ) {
				exit;
			}
		} else {
			$io->writeln( [
					'You have not selected any additional components for your plugin',
					'(Available components are: ' . \join( ', ', \array_map( function ( $i ) {
							return "<comment>$i</comment>";
						}, $this->available_components ) ) . ')',
				] );

			if ( false === $io->confirm( "Is that Ok for you ? ", true ) ) {
				exit;
			}
		}

		$downloadPath = $composer->getConfig()->get( 'vendor-dir' ) . '/boilerplate';
		$pluginPath   = dirname( $composer->getConfig()->get( 'vendor-dir' ) ) . '/content/plugins';

		if ( is_dir( $pluginPath . '/' . $pluginName ) ) {
			$io->error( "A plugin with this folder's name already exist." );
			exit;
		}

		// Ensure we have boilerplate plugin locally
		if ( ! file_exists( $downloadPath . '/bea-plugin-boilerplate.php' ) ) {
			$composer->getDownloadManager()->download( $this->getPluginBoilerplatePackage(), $downloadPath );
		}

		if ( ! file_exists( $downloadPath . '/bea-plugin-boilerplate.php' ) ) {
			$io->error( "Couldn't download plugin boilerplate from Github." );
			exit;
		}

		if ( ! mkdir( $pluginPath . '/' . $pluginName ) ) {
			$io->error( "Couldn't create the plugin directory." );
			exit;
		}

		// Basic plugin files
		mkdir( $pluginPath . '/' . $pluginName . '/classes/admin/', 0755, true );

		rename( $downloadPath . '/bea-plugin-boilerplate.php', $pluginPath . '/' . $pluginName . '/' . $pluginName . '.php' );
		rename( $downloadPath . '/compat.php', $pluginPath . '/' . $pluginName . '/compat.php' );
		rename( $downloadPath . '/autoload.php', $pluginPath . '/' . $pluginName . '/autoload.php' );

		// Basic plugin classes
		rename( $downloadPath . '/classes/plugin.php', $pluginPath . '/' . $pluginName . '/classes/plugin.php' );
		rename( $downloadPath . '/classes/main.php', $pluginPath . '/' . $pluginName . '/classes/main.php' );
		rename( $downloadPath . '/classes/helpers.php', $pluginPath . '/' . $pluginName . '/classes/helpers.php' );
		rename( $downloadPath . '/classes/singleton.php', $pluginPath . '/' . $pluginName . '/classes/singleton.php' );
		rename( $downloadPath . '/classes/admin/main.php', $pluginPath . '/' . $pluginName . '/classes/admin/main.php' );

		foreach ( $this->available_components as $component ) {
			if ( ! in_array( $component, $components ) ) {
				continue;
			}

			switch ( $component ) {
				case 'controller':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/controllers/' );
					rename( $downloadPath . '/classes/controllers/controller.php', $pluginPath . '/' . $pluginName . '/classes/controllers/controller.php' );
					break;
				case 'cron':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/cron/' );
					rename( $downloadPath . '/classes/cron/cron.php', $pluginPath . '/' . $pluginName . '/classes/cron/cron.php' );
					break;
				case 'model':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/models/' );
					rename( $downloadPath . '/classes/models/model.php', $pluginPath . '/' . $pluginName . '/classes/models/model.php' );
					rename( $downloadPath . '/classes/models/user.php', $pluginPath . '/' . $pluginName . '/classes/models/user.php' );
					break;
				case 'route':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/routes/' );
					rename( $downloadPath . '/classes/routes/router.php', $pluginPath . '/' . $pluginName . '/classes/routes/router.php' );
					break;
				case 'widget':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/widgets/' );
					mkdir( $pluginPath . '/' . $pluginName . '/views/' );
					mkdir( $pluginPath . '/' . $pluginName . '/views/admin/' );
					mkdir( $pluginPath . '/' . $pluginName . '/views/client/' );

					// Class
					rename( $downloadPath . '/classes/widgets/main.php', $pluginPath . '/' . $pluginName . '/classes/widgets/main.php' );

					// Views
					rename( $downloadPath . '/views/admin/widget.php', $pluginPath . '/' . $pluginName . '/views/admin/widget.php' );
					rename( $downloadPath . '/views/client/widget.php', $pluginPath . '/' . $pluginName . '/views/client/widget.php' );
					break;
				case 'shortcode':
					mkdir( $pluginPath . '/' . $pluginName . '/classes/shortcodes/' );
					rename( $downloadPath . '/classes/shortcodes/shortcode.php', $pluginPath . '/' . $pluginName . '/classes/shortcodes/shortcode.php' );
					rename( $downloadPath . '/classes/shortcodes/shortcode-factory.php', $pluginPath . '/' . $pluginName . '/classes/shortcodes/shortcode-factory.php' );
					break;
			}
		}

		// Replace
		$pluginCompletePath = $pluginPath . '/' . $pluginName . '/';

		// text domain
		self::doStrReplace( $pluginCompletePath, 'bea-plugin-boilerplate', $pluginName );

		// init function
		self::doStrReplace( $pluginCompletePath, 'init_bea_pb_plugin', 'init_' . str_replace( '-', '_', $pluginName ) . '_plugin' );

		$io->writeln( '' );

		// plugin human name
		$pluginRealName = $this->askAndConfirm( $io, "What is your plugin real name ? (e.g: 'My great plugin') " );
		self::doStrReplace( $pluginCompletePath, 'BEA Plugin Name', $pluginRealName );

		// namespace
		$pluginNamespace = $this->askAndConfirm( $io, "What is your plugin's namespace ? (e.g: 'My_company\\My_Plugin') " );
		self::doStrReplace( $pluginCompletePath, 'BEA\\PB', $pluginNamespace );

		// constants prefix
		$pluginConstsPrefix = $this->askAndConfirm( $io, "What is your constants prefix ? (e.g: 'MY_COMPANY_MY_PLUGIN_') " );
		if ( '_' !== substr( $pluginConstsPrefix, - 1 ) ) {
			$pluginConstsPrefix = $pluginConstsPrefix . '_';
		}
		self::doStrReplace( $pluginCompletePath, 'BEA_PB_', $pluginConstsPrefix );

		// view folder
		$pluginViewFolderName = $this->askAndConfirm( $io, "What is your plugin's view folder name ? (e.g: 'my-plugin') " );
		self::doStrReplace( $pluginCompletePath, 'bea-pb', $pluginViewFolderName );

		$io->success( 'Your plugin is ready ! :)' );
	}

	/**
	 * Ask the user for a value and then ask for confirmation
	 *
	 * @param SymfonyStyle $io           Composer IO object
	 * @param string       $question     question to ask to the user
	 * @param string       $confirmation confirmation message
	 *
	 * @return string
	 */
	protected function askAndConfirm( SymfonyStyle $io, $question, $confirmation = '' ) {
		$value = '';
		while( empty( $value ) ) {
			$value = trim( $io->ask( $question ) );
		}

		if ( empty( $confirmation ) ) {
			$confirm_msg = sprintf( 'You have enter %s. Is that Ok ? ', $value );
		} else {
			$confirm_msg = sprintf( $confirmation, $value );
		}

		if ( $io->confirm( $confirm_msg ) ) {
			return $value;
		}

		return $this->askAndConfirm( $io, $question, $confirmation );
	}

	/**
	 * Do a search/replace in folder
	 *
	 * @param string $path
	 * @param string $search
	 * @param string $replace
	 * @param string $extension
	 *
	 * @return bool
	 * @internal param string $needle what to replace
	 */
	protected function doStrReplace( $path, $search, $replace = '', $extension = 'php' ) {
		if ( empty( $path ) || empty( $search ) ) {
			return false;
		}

		$path     = realpath( $path );
		$fileList = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $path ), \RecursiveIteratorIterator::SELF_FIRST );

		foreach ( $fileList as $item ) {
			if ( $item->isFile() && false !== stripos( $item->getPathName(), $extension ) ) {
				$content = file_get_contents( $item->getPathName() );
				file_put_contents( $item->getPathName(), str_replace( $search, $replace, $content ) );
			}
		}

		return true;
	}

	/**
	 * Setup a dummy package for Composer to download
	 *
	 * @return Package
	 */
	protected function getPluginBoilerplatePackage() {
		$p = new Package( 'plugin-boilerplate', 'dev-master', 'Latest' );
		$p->setType( 'library' );
		$p->setDistType( 'zip' );
		$p->setDistUrl( 'https://github.com/BeAPI/bea-plugin-boilerplate/archive/master.zip' );

		return $p;
	}
}
