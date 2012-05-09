<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Upgrade\Database\DatabaseUpgradeRunner;
use Supra\Upgrade\Database\SqlUpgradeFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Console\Output\ArrayOutput;
use Supra\Upgrade\Exception;

/**
 * Database upgrade command
 */
class DatabaseUpgradeCommand extends Command
{

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @var boolean
	 */
	protected $force;

	/**
	 * @var boolean
	 */
	protected $dumpSql;

	/**
	 * @var boolean
	 */
	protected $list;

	protected function configure()
	{
		$this->setName('su:upgrade:database')
				->setDescription('Runs database upgrades.')
				->setHelp('Upgrades database.')
				->setDefinition(array(
					new InputOption(
							'force', null, InputOption::VALUE_NONE,
							'Causes the generated SQL statements to be physically executed against your database.'
					),
					new InputOption(
							'dump-sql', null, InputOption::VALUE_NONE,
							'Dumps SQL statements to the output.'
					),
					new InputOption(
							'list', null, InputOption::VALUE_NONE,
							'Lists filenames of upgrades ready to be executed.'
					),
					new InputOption(
							'check', null, InputOption::VALUE_NONE,
							'Causes exception if there are any pending updates.'
					),
				));
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;

		$this->output->writeln('Running database upgrades...');

		$this->output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');

		$this->force = (true === $input->getOption('force'));
		$this->list = (true === $input->getOption('list'));

		$this->dumpSql = (true === $input->getOption('dump-sql'));
		$this->check = (true === $input->getOption('check'));

		$this->runDatabaseUpgrades();

		$this->output->writeln('Done running database upgrades.');

		$this->runSchemaUpdateCommand();
	}

	protected function runDatabaseUpgrades()
	{
		$supraUpgradeRunner = new DatabaseUpgradeRunner();

		$supraUpgradeRunner->setOutput($this->output);
		$supraUpgradeRunner->setDumpSql($this->dumpSql);
		$supraUpgradeRunner->setForce($this->force);

		$pendingUpgrades = $supraUpgradeRunner->getPendingUpgrades();

		if (empty($pendingUpgrades)) {

			$this->output->writeln('No pending database upgrades.');

			return;
		}

		$this->output->writeln('Have ' . count($pendingUpgrades) . ' pending database upgrade(s):');

		$this->output->writeln('');
		
		foreach ($pendingUpgrades as $file) {
			/* @var $file SqlUpgradeFile */
			$this->output->writeln("\t\\. " . $file->getPathname());
		}
		
		$this->output->writeln('');

		// Whether this executes sometging is up to values of $dumpSql and 
		// $force passed into upgrade runner earlier.
		$supraUpgradeRunner->executePendingUpgrades();

		if ($this->check) {
			throw new Exception\RuntimeException('Database is not up to date. Some upgrade(s) are still pending.');
		}
	}

	protected function runSchemaUpdateCommand()
	{
		$array = array('su:schema:update');

		if ($this->force) {
			$array['--force'] = true;
		}
		if ($this->list) {
			$array['--dump-sql'] = true;
		}

		if ($this->check) {
			$array['--check'] = true;
		}

		$input = new ArrayInput($array);

		$this->getApplication()->run($input, $this->output);
	}

}