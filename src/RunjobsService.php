<?php

namespace BlueSpice\Service\ParallelRunJobs;

use mysqli;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RunjobsService {

	/** @var array */
	private $config;

	/** @var OutputInterface */
	private $output;

	public function __construct( array $config, OutputInterface $output ) {
		$this->config = $config;
		$this->output = $output;
		$this->get_sql_creds();
		$this->config['wiki']['instancesDir'] = $this->config['wiki']['path'] . DIRECTORY_SEPARATOR . '_sf_instances';
	}

	public function run() {
		while ( true ) {
			$this->output->clear();
			if ( $this->config['wiki']['type'] == "farm" ) {
				$this->output->writeln( '<info>Detected ' . $this->config['wiki']['type'] . ' wiki installation<\info>' );
				$instanceList = $this->generate_instance_list();
				$this->run_parallel( $instanceList );
			}
			$this->run_single();
			$this->output->writeln( "<info>Cooling down</info>" );
			sleep( $this->config['runjobs']['cooldown'] );
		}
	}

	private function parse_php_vars( $php_file, $src_str = false ) {
		$content = file_get_contents( $php_file );
		$reg = '/\$(?P<variable>\w+)\s*=\s*"?\'?(?P<value>[^"\';]+)"?\'?;/i';
		preg_match_all( $reg, $content, $matches, PREG_SET_ORDER );

		if ( !$src_str ) {
			$pvalue = $matches;
		} else {
			$pvalue = false;
			foreach ( $matches as $line ) {
				if ( $line['variable'] == $src_str ) {
					$pvalue = $line['value'];
					break;
				}
			}
		}

		return $pvalue;
	}

	private function get_sql_creds() {
		$phpFilePath = $this->config['wiki']['path'] . '/' . $this->config['wiki']['reference'];

		$sqlHost = $this->parse_php_vars( $phpFilePath, 'wgDBserver' );
		$sqlUser = $this->parse_php_vars( $phpFilePath, 'wgDBuser' );
		$sqlPass = $this->parse_php_vars( $phpFilePath, 'wgDBpassword' );

		$this->sqlHost = $sqlHost;
		$this->sqlUser = $sqlUser;
		$this->sqlPass = $sqlPass;
	}

	private function count_jobs( $instanceName ) {
		$sqlDatabase = $this->parse_php_vars(
			$this->config['wiki']['instancesDir'] . DIRECTORY_SEPARATOR . $instanceName . DIRECTORY_SEPARATOR . 'LocalSettings.php',
			'wgDBname'
		);
		$value = 0;
		$db = new mysqli( $this->sqlHost, $this->sqlUser, $this->sqlPass, $sqlDatabase );

		if ( $db->connect_error ) {
			throw new Exception( "Connection failed: " . $db->connect_error );
		}
		$sql = $db->prepare( "SELECT COUNT(*) FROM job WHERE job_token=''" );
		$sql->execute();
		$sql->bind_result( $jobCount );
		$sql->fetch();
		$value = (int)$jobCount;
		$sql->close();
		$db->close();

		return $value;
	}

	private function generate_instance_list() {
		// generate instance list
		$this->output->writeln( '<info>generating instance list<\info>' );
		$instanceList = [];
		$allWikiInstances = array_map( 'basename', array_filter( glob( $this->config['wiki']['instancesDir'] . '/*' ), 'is_dir' ) );
		$this->output->writeln( "<info>generated instance list: " . implode( ', ', $allWikiInstances ) . '<\info>' );

		// Check if both the include list and exclude list have valid instance names
		if ( !empty( $this->config['include-instances'] ) ) {
			if ( !empty( array_diff( $this->config['include-instances'], $allWikiInstances ) ) ) {
				throw new \RuntimeException( "Invalid include-instances" );
			}
		}

		if ( !empty( $this->config['exclude-instances'] ) ) {
			if ( !empty( array_diff( $this->config['exclude-instances'], $allWikiInstances ) ) ) {
				throw new \RuntimeException( "Invalid exclude-instances" );
			}
		}

		// Determine the instances list based on include-instances and exclude-instances
		if ( empty( $this->config['include-instances'] ) ) {
			$instances = $allWikiInstances;
		} else {
			$instances = $this->config['include-instances'];
		}

		// Make sure the resultant list is mutually exclusive and get final instance list
		$instances = array_diff( $instances, $this->config['exclude-instances'] );
		$this->output->writeln( "<\info>target instances: " . implode( ', ', $instances ) . "<\info>" );

		foreach ( $instances as $instance ) {
			$instancePath = $this->config['wiki']['instancesDir'] . DIRECTORY_SEPARATOR . $instance;

			if ( !file_exists( $instancePath . DIRECTORY_SEPARATOR . 'SUSPENDED' ) && file_exists( $instancePath . DIRECTORY_SEPARATOR . 'LocalSettings.php' ) ) {
				$this->output->writeln( "<\info>farm instance '$instance' is not suspended and has LocalSettings.php<\info>" );

				$jobCount = $this->count_jobs( $instance, $this->config, $this->sqlHost, $this->sqlUser, $this->sqlPass );

				if ( $jobCount > 0 ) {
					$this->output->writeln( "<\info>farm instance '$instance' has $jobCount pending jobs<\info>" );

					$instanceTuple = [ $instance, $jobCount ];
					$instanceList[] = $instanceTuple;
				} else {
					$this->output->writeln( "<\info>farm instance '$instance' has no pending jobs<\info>" );
				}
			}
		}

		return $instanceList;
	}

	private function run_parallel( $instanceList ) {
		$this->output->writeln( "<\info>Running jobs in parallel<\info>" );

		$jobs = [];
		$numParallelJobs = $this->config['runjobs']['maxforkprocesses'];

		// Start the parallel jobs
		for ( $i = 0; $i < $numParallelJobs; $i++ ) {

			// Create a new Process for each job
			$currInstanceName = $instanceList[$i][0];
			$currInstanceJobCount = $instanceList[$i][1];
			$cmd = [ '/usr/bin/php', $this->config['wiki']['path'] . '/maintenance/runJobs.php', '--maxtime=' . $this->config['runjobs']['maxtime'] ];
			if ( $currInstanceJobCount > 100 ) {
				$maxjobs = (int)( ( $currInstanceJobCount / 100 ) * $this->config['runjobs']['percentage'] );
				$cmd[] = '--maxjobs=' . $maxjobs;
			}
			$cmd[] = '--sfr=' . $currInstanceName;
			$process = new Process( $cmd );
			$process->start();
			$jobs[] = $process;
		}

		foreach ( $jobs as $process ) {
			$process->wait();
		}

		$this->output->writeln( "All current parallel jobs have finished" );
	}

	private function run_single() {
		$output = $this->output;
		$cmd = [ '/usr/bin/php', $this->config['wiki']['path'] . '/maintenance/runJobs.php', '--maxtime=' . $this->config['runjobs']['maxtime'] ];
		$process = new Process( $cmd );
		$process->run( static function ( $type, $buffer ) use ( $output ) {
			$output->write( $buffer );
		} );
	}
}
