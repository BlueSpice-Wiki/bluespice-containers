<?php

namespace BlueSpice\Service\ParallelRunJobs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class RunjobsCommand extends Command {

	/** @var string */
	protected static $defaultName = 'runjobs';

	/** @var OutputInterface */
	private $output;

	protected function configure() {
		$this
			->setDescription( 'Execute runjobs service parallelly for multiple instances.' )
			->setHelp( 'This command executes the runjobs service for multiple wiki farm instances as well as non-farm instances.' )
			->addOption( 'config', 'c', InputOption::VALUE_OPTIONAL, 'Path to the configuration file (YAML). If not provided, the default path will be used.' )
			->addOption( 'wiki-type', null, InputOption::VALUE_OPTIONAL, 'Type of wiki (standalone or farm).' )
			->addOption( 'wiki-path', null, InputOption::VALUE_OPTIONAL, 'Absolute path of the wiki.' )
			->addOption( 'wiki-reference', null, InputOption::VALUE_OPTIONAL, 'Reference file for the wiki (e.g., LocalSettings.php).' )
			->addOption( 'runjobs-percentage', null, InputOption::VALUE_OPTIONAL, 'Maximum percentage of total jobs (per wiki, per cycle).' )
			->addOption( 'runjobs-maxtime', null, InputOption::VALUE_OPTIONAL, 'Maximum life time of a runJobs.php (per wiki, per cycle) in seconds.' )
			->addOption( 'runjobs-cooldown', null, InputOption::VALUE_OPTIONAL, 'Wait time after each cycle in seconds.' )
			->addOption( 'runjobs-maxforkprocesses', null, InputOption::VALUE_OPTIONAL, 'Maximum number of sub processes that can be spawned for parallel processing' )
			->addOption( 'exclude-instances', null, InputOption::VALUE_OPTIONAL, 'Contains list of comma separated farm instances name for which the runjobs should not be run' )
			->addOption( 'include-instances', null, InputOption::VALUE_OPTIONAL, 'Contains list of comma separated farm instances name for which  the runjobs should be run, if left empty, all the farm instances will be considered except the ones in "exclude-instances" list' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$this->output = $output->section();
		$runjobsOutputSection = $output->section();

		if ( $input->getOption( 'config' ) && $this->hasOtherOptions( $input ) ) {
			throw new \RuntimeException( "The '--config' option cannot be used along with other options." );
		}

		// Check if the config file was provided; if not, use provided options as config
		if ( !$input->getOption( 'config' ) ) {
			$this->validateMandatoryOptions( $input );
			$config = $this->buildConfigFromOptions( $input );
		} else {
			$configFilePath = $input->getOption( 'config' );
			$config = $this->loadConfig( $configFilePath );
		}

		$this->output->writeln( '<info>Started executing runjobs service</info>' );

		$runjobsService = new RunjobsService( $config, $runjobsOutputSection );
		$runjobsService->run();

		return Command::SUCCESS;
	}

	protected function loadConfig( string $configFilePath ) {
		if ( !file_exists( $configFilePath ) ) {
			throw new \RuntimeException( "Configuration file not found at: $configFilePath" );
		}

		return Yaml::parseFile( $configFilePath );
	}

	protected function validateMandatoryOptions( InputInterface $input ) {
		$mandatoryOptions = [ 'wiki-type', 'wiki-path' ];

		foreach ( $mandatoryOptions as $option ) {
			if ( !$input->getOption( $option ) ) {
				throw new \RuntimeException( "The '--$option' option is mandatory when no config file is provided." );
			}
		}
	}

	protected function buildConfigFromOptions( InputInterface $input ) {
		return [
			'wiki' => [
				'type' => $input->getOption( 'wiki-type' ),
				'path' => $input->getOption( 'wiki-path' ),
				'reference' => $input->getOption( 'wiki-reference' ) ?? 'LocalSettings.php',
			],
			'runjobs' => [
				'percentage' => $input->getOption( 'runjobs-percentage' ) ?? 50,
				'maxtime' => $input->getOption( 'runjobs-maxtime' ) ?? 10,
				'cooldown' => $input->getOption( 'runjobs-cooldown' ) ?? 3,
				'maxforkprocesses' => $input->getOption( 'runjobs-maxforkprocesses' ) ?? 5,
			],
			'exclude-instances' => $input->getOption( 'exclude-instances' ) ?? [],
			'include-instances' => $input->getOption( 'include-instances' ) ?? [],
		];
	}

	protected function hasOtherOptions( InputInterface $input ) {
		$otherOptions = [ 'wiki-type', 'wiki-path', 'wiki-reference', 'runjobs-percentage', 'runjobs-maxtime', 'runjobs-cooldown', 'runjobs-maxforkprocesses', 'exclude-instances', 'include-instances' ];

		foreach ( $otherOptions as $option ) {
			if ( $input->getOption( $option ) ) {
				return true;
			}
		}

		return false;
	}
}
