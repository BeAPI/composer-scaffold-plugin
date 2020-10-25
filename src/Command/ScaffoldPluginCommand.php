<?php namespace BEA\Composer\ScaffoldPlugin\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScaffoldPluginCommand extends BaseCommand {

	const WP_PLUGIN_PACKAGE_TYPE = 'wordpress-plugin';

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
		$this->setName( 'scaffold-plugin' )
		     ->setDescription( 'Bootstrap a new WordPress plugin using Be API\'s boilerplate.' )
		     ->addArgument( 'folder', InputArgument::REQUIRED, "Your plugin's folder name" )
		     ->addArgument( 'components', InputArgument::IS_ARRAY, "Optional components you want to include in your plugin.\n Available components are :\n\t- Controller\n\t- Cron\n\t- Model\n\t- Route\n\t- Widget\n\t- Shortcode" )
		     ->addOption( 'boilerplate-version', null, InputOption::VALUE_OPTIONAL, 'Wich version of boilerplate to use', 'Latest' )
		     ->addOption( 'no-autoload', null, InputOption::VALUE_NONE, 'Do not Autoload the class in composer.json' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$io       = new SymfonyStyle( $input, $output );
		$composer = $this->getComposer();

		$pluginName  = $input->getArgument( 'folder' );
		$components  = $input->getArgument( 'components' );
		$version     = $input->getOption( 'boilerplate-version' );
		$no_autoload = $input->getOption( 'no-autoload' );

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

		try {
			$installPath = $this->getInstallPath( $pluginName, $composer );
		} catch ( \InvalidArgumentException $e ) {
			$io->error( "Couldn't get WordPress plugins directory." );
			exit;
		}

		if ( is_dir( $installPath ) ) {
			$io->error( "A plugin with this folder's name already exist." );
			exit;
		}

		// Ensure we have boilerplate plugin locally
		if ( ! file_exists( $downloadPath . '/bea-plugin-boilerplate.php' ) ) {
			$package = $this->getPluginBoilerplatePackage( $version );
			$this->downloadPackage( $composer, $package, $downloadPath );
		}

		if ( ! file_exists( $downloadPath . '/bea-plugin-boilerplate.php' ) ) {
			$io->error( "Couldn't download plugin boilerplate from Github." );
			exit;
		}

		if ( ! mkdir( $installPath ) ) {
			$io->error( "Couldn't create the plugin directory." );
			exit;
		}

		$is_psr_4 = ! file_exists( $downloadPath . '/autoload.php' );
		if ( $is_psr_4 ) {
			$io->writeln( '<info>Plugin with PSR-4 detected</info>' );
		} else {
			$io->writeln( '<info>Plugin without PSR-4 detected</info>' );
		}

		// Basic plugin files
		if ( ! $is_psr_4 ) {
			mkdir( $installPath . '/classes/admin/', 0755, true );
		} else {
			mkdir( $installPath . '/classes/Admin/', 0755, true );
		}
		rename( $downloadPath . '/bea-plugin-boilerplate.php', $installPath . $pluginName . '.php' );

		if ( ! $is_psr_4 ) {
			/**
			 * Non PSR-4
			 */
			rename( $downloadPath . '/compat.php', $installPath . 'compat.php' );
			rename( $downloadPath . '/autoload.php', $installPath . 'autoload.php' );

			// Basic plugin classes
			rename( $downloadPath . '/classes/plugin.php', $installPath . 'classes/plugin.php' );
			rename( $downloadPath . '/classes/main.php', $installPath . 'classes/main.php' );
			rename( $downloadPath . '/classes/helpers.php', $installPath . 'classes/helpers.php' );
			rename( $downloadPath . '/classes/singleton.php', $installPath . 'classes/singleton.php' );
			rename( $downloadPath . '/classes/admin/main.php', $installPath . 'classes/admin/main.php' );
		} else {
			/**
			 * PSR-4
			 */
			// Basic plugin classes
			rename( $downloadPath . '/classes/Compatibility.php', $installPath . 'classes/Compatibility.php' );
			rename( $downloadPath . '/classes/Plugin.php', $installPath . 'classes/Plugin.php' );
			rename( $downloadPath . '/classes/Main.php', $installPath . 'classes/Main.php' );
			rename( $downloadPath . '/classes/Helpers.php', $installPath . 'classes/Helpers.php' );
			rename( $downloadPath . '/classes/Singleton.php', $installPath . 'classes/Singleton.php' );
			rename( $downloadPath . '/classes/Admin/Main.php', $installPath . 'classes/Admin/Main.php' );
		}

		foreach ( $this->available_components as $component ) {
			if ( ! in_array( $component, $components ) ) {
				continue;
			}

			switch ( $component ) {
				case 'controller':
					if ( ! $is_psr_4 ) {
						mkdir( $installPath . 'classes/controllers/' );
						rename( $downloadPath . '/classes/controllers/controller.php', $installPath . 'classes/controllers/controller.php' );
					} else {
						mkdir( $installPath . 'classes/Controllers/' );
						rename( $downloadPath . '/classes/Controllers/Controller.php', $installPath . 'classes/Controllers/Controller.php' );
					}
					break;
				case 'cron':
					if ( ! $is_psr_4 ) {
						mkdir( $installPath . 'classes/cron/' );
						rename( $downloadPath . '/classes/cron/cron.php', $installPath . 'classes/cron/cron.php' );
					} else {
						mkdir( $installPath . 'classes/Cron/' );
						rename( $downloadPath . '/classes/Cron/Cron.php', $installPath . 'classes/Cron/Cron.php' );
					}
					break;
				case 'model':
					if ( ! $is_psr_4 ) {
						mkdir( $installPath . 'classes/models/' );
						rename( $downloadPath . '/classes/models/model.php', $installPath . 'classes/models/model.php' );
						rename( $downloadPath . '/classes/models/user.php', $installPath . 'classes/models/user.php' );
					} else {
						mkdir( $installPath . 'classes/Models/' );
						rename( $downloadPath . '/classes/Models/Model.php', $installPath . 'classes/Models/Model.php' );
						rename( $downloadPath . '/classes/Models/User.php', $installPath . 'classes/Models/User.php' );
					}
					break;
				case 'route':
					if ( ! $is_psr_4 ) {
						mkdir( $installPath . 'classes/routes/' );
						rename( $downloadPath . '/classes/routes/router.php', $installPath . 'classes/routes/router.php' );
					} else {
						mkdir( $installPath . 'classes/Routes/' );
						rename( $downloadPath . '/classes/Routes/Router.php', $installPath . 'classes/Routes/Router.php' );
					}
					break;
				case 'widget':
					if ( ! \is_dir($downloadPath . '/views/' ) ) {
						$io->writeln( '<error>Widgets aren\'t supported on your version, no folders will be generated.</error>' );
						continue 2;
					}

					mkdir( $installPath . 'views/' );
					mkdir( $installPath . 'views/admin/' );
					mkdir( $installPath . 'views/client/' );
					// Views
					rename( $downloadPath . '/views/admin/widget.php', $installPath . 'views/admin/widget.php' );
					rename( $downloadPath . '/views/client/widget.php', $installPath . 'views/client/widget.php' );

					mkdir( $installPath . 'classes/widgets/' );

					// Class
					rename( $downloadPath . '/classes/widgets/main.php', $installPath . 'classes/widgets/main.php' );
					break;
				case 'shortcode':
					if ( ! $is_psr_4 ) {

						mkdir( $installPath . 'classes/shortcodes/' );
						rename( $downloadPath . '/classes/shortcodes/shortcode.php', $installPath . 'classes/shortcodes/shortcode.php' );
						rename( $downloadPath . '/classes/shortcodes/shortcode-factory.php', $installPath . 'classes/shortcodes/shortcode-factory.php' );
					} else {
						mkdir( $installPath . 'classes/Shortcodes/' );

						rename( $downloadPath . '/classes/Shortcodes/Shortcode.php', $installPath . 'classes/Shortcodes/Shortcode.php' );
						rename( $downloadPath . '/classes/Shortcodes/Shortcode_Factory.php', $installPath . 'classes/Shortcodes/Shortcode_Factory.php' );

					}
					break;
			}
		}

		// text domain
		self::doStrReplace( $installPath, 'bea-plugin-boilerplate', $pluginName );

		// init function
		self::doStrReplace( $installPath, 'init_bea_pb_plugin', 'init_' . str_replace( '-', '_', $pluginName ) . '_plugin' );
		$io->writeln( '' );

		// plugin human name
		$pluginRealName = $this->askAndConfirm( $io, "What is your plugin real name ? (e.g: 'My great plugin') " );
		self::doStrReplace( $installPath, 'BEA Plugin Name', $pluginRealName );

		// namespace
		$pluginNamespace = $this->askAndConfirm( $io, "What is your plugin's namespace ? (e.g: 'My_company\\My_Plugin') " );
		self::doStrReplace( $installPath, 'BEA\\PB', $pluginNamespace );

		// constants prefix
		$pluginConstsPrefix = $this->askAndConfirm( $io, "What is your constants prefix ? (e.g: 'MY_COMPANY_MY_PLUGIN_') " );
		if ( '_' !== substr( $pluginConstsPrefix, - 1 ) ) {
			$pluginConstsPrefix = $pluginConstsPrefix . '_';
		}
		self::doStrReplace( $installPath, 'BEA_PB_', $pluginConstsPrefix );

		// view folder
		$pluginViewFolderName = $this->askAndConfirm( $io, "What is your plugin's view folder name ? (e.g: 'my-plugin') " );
		self::doStrReplace( $installPath, 'bea-pb', $pluginViewFolderName );

		if ( $is_psr_4 && false === $no_autoload ) {

			/**
			 * Add the new namespace to the autoload entry of the composer.json file.
			 */
			$composerPath = $composer->getConfig()->getConfigSource()->getName();
			$composerFile = new JsonFile( $composerPath );

			try {
				$composerJson                                                 = $composerFile->read();
				$composerJson['autoload']['psr-4'][ $pluginNamespace . "\\" ] = rtrim( $installPath, '/' ) . '/classes/';

				$composerFile->write( $composerJson );
				$output->writeln( "The namespace have been added to the composer.json file !" );
			} catch ( RuntimeException $e ) {
				$output->writeln( "<error>An error occurred</error>" );
				$output->writeln( sprintf( "<error>%s</error>", $e->getMessage() ) );
				exit;
			}

			$io->success( 'Run composer dump-autoload to make the autoloading work :)' );
		}
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
	 * Download a package.
	 *
	 * @param Composer $composer
	 * @param Package $package
	 * @param string $path
	 */
	protected function downloadPackage( Composer $composer, Package $package, $path ) {
		if ( version_compare( Composer::RUNTIME_API_VERSION, '2.0', '>=' ) ) {
			$promise = $composer->getDownloadManager()->download( $package, $path );
			$composer->getLoop()->wait([$promise]);
			$promise = $composer->getDownloadManager()->install($package, $path);
			$composer->getLoop()->wait([$promise]);
		} else {
			$composer
				->getDownloadManager()
				->download($package, $path);
		}
	}

	/**
	 * Setup a dummy package for Composer to download
	 *
	 * @param $version
	 *
	 * @return Package
	 */
	protected function getPluginBoilerplatePackage( $version ) {
		$p = new Package( 'plugin-boilerplate', 'dev-master', $version );
		$p->setType( 'library' );
		$p->setInstallationSource('dist');
		$p->setDistType( 'zip' );

		$dist_url = "https://github.com/BeAPI/bea-plugin-boilerplate/archive/master.zip";

		if ( $version !== 'Latest' ) {
			$dist_url = sprintf( 'https://github.com/BeAPI/bea-plugin-boilerplate/archive/%s.zip', $version );
		}

		$p->setDistUrl( $dist_url );

		return $p;
	}

	/**
	 * Create dummy wordpress-plugin package to get the installation path
	 *
	 * @param string $pluginName
	 * @param Composer $composer
	 *
	 * @return string
	 */
	protected function getInstallPath( $pluginName, $composer ) {
		$plugin = new Package( $pluginName, 'dev-master', 'Latest' );
		$plugin->setType( self::WP_PLUGIN_PACKAGE_TYPE );
		$path = $composer->getInstallationManager()->getInstallPath( $plugin );

		return \rtrim( $path, '/' ) . '/';
	}
}
