<?php

namespace Supra\Console\Cron;

use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Supra\Console\Cron\Entity\CronJob;
use Supra\Console\Application;


/**
 * Master cron command
 */
class Command extends SymfonyCommand
{
	
	/**
	 * Configure
	 */
	protected function configure() 
	{
		$this->setName('su:cron')
				->setDescription('Master cron job.')
				->setHelp('Master cron job.');
	}

	/**
	 * Execute
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{

		require_once SUPRA_CONF_PATH . '/cron.php';
		
		$em = ObjectRepository::getEntityManager($this);
		$masterCronJob = $this->getMasterCronEntity();

		$em->getConnection()->beginTransaction();
		$em->lock($masterCronJob, \Doctrine\DBAL\LockMode::PESSIMISTIC_READ);
		
		$lastTime = $masterCronJob->getLastExecutionTime();
		$thisTime = new \DateTime();

		$repo = $em->getRepository(CronJob::CN());
		/* @var $repo Repository\CronJobRepository */
		$jobs = $repo->findScheduled($lastTime, $thisTime);

		$cli = Application::getInstance();

		foreach ($jobs as $job) {
			/* @var $job CronJob */
			
			if ($job->getCommandInput() == $this->getName()) {
				continue;
			}
			
			$log = ObjectRepository::getLogger($this);
			
			$jobStatus = $job->getStatus();
			switch ($jobStatus) {
				case CronJob::STATUS_NEW:
				case CronJob::STATUS_OK:
				case CronJob::STATUS_FAILED:
					
					// Lock job
					$job->setStatus(CronJob::STATUS_LOCKED);
					$em->flush();
					
					$commandInput = new StringInput($job->getCommandInput());
					$commandOutput = new \Supra\Console\Output\ArrayOutput();
					
					try {

						$return = $cli->doRun($commandInput, $commandOutput);
						
						// workaround for cleared unit of work
						$job = $repo->find($job->getId());
						
						if ($return === 0) {
							$output = $commandOutput->getOutput();
							$log->info("Scheduled task {$job->getCommandInput()} finished with output ", $output);
							$job->setStatus(CronJob::STATUS_OK);
							
							// will mean last success execution time
							$job->setLastExecutionTime($thisTime);
						} else {
							$output = $commandOutput->getOutput();
							$log->error("Scheduled task {$job->getCommandInput()} has non-zero return code {$return} and output ", $output);
							$job->setStatus(CronJob::STATUS_FAILED);
						}
					} catch (\Exception $e) {
						
						$job = $repo->find($job->getId());
						
						$output = $commandOutput->getOutput();
						$log->error("Unexpected failure while running scheduled task {$job->getCommandInput()}: {$e->__toString()}\nOutput: ", $output);
						
						$job->setStatus(CronJob::STATUS_FAILED);
					}
					
					$this->updateJobNextExecutionTime($job);
					
					break;
					
				case CronJob::STATUS_LOCKED:
					// unlock still locked job
					$job->setStatus(CronJob::STATUS_FAILED);
				default:
					
					$this->updateJobNextExecutionTime($job);
					
					// nothing
					break;
			}
			
			$em->flush();
		}

		// if someone will clear UnitOfWork, master cron job will have status `detached`
		// so it would be safer to fetch it one more time
		$masterCronJob = $this->getMasterCronEntity();
		$masterCronJob->setLastExecutionTime($thisTime);
		
		$em->lock($masterCronJob, \Doctrine\DBAL\LockMode::NONE);
		$em->flush();
		
		$em->getConnection()
				->commit();
		
	}

	/**
	 * Get master cron job entity (creates new if not found)
	 *
	 * @return CronJob 
	 */
	protected function getMasterCronEntity()
	{
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository(CronJob::CN());
		/* @var $repo Repository\CronJobRepository */
		$entity = $repo->findOneByCommandInput($this->getName());
		
		if ( ! $entity instanceof CronJob) {
			$entity = new CronJob();
			$entity->setCommandInput($this->getName());
			$lastTime = new \DateTime();
			$lastTime->setTimestamp(0);
			$entity->setLastExecutionTime($lastTime);
			$entity->setStatus(CronJob::STATUS_MASTER);
			
			$em->persist($entity);
			//$em->flush();
		}
		
		return $entity;
	}
	
	/**
	 * Update job's next execution time
	 *
	 * @param CronJob $job 
	 */
	protected function updateJobNextExecutionTime(CronJob $job)
	{
		$periodClass = $job->getPeriodClass();
		$period = new $periodClass($job->getPeriodParameter());
		/* @var $period Period\PeriodInterface */
		$previousTime = $job->getNextExecutionTime();
		$nextTime = $period->getNext($previousTime);
		$job->setNextExecutionTime($nextTime);
	}
	
}
