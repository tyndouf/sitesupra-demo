<?php

// Supra starting
require_once __DIR__ . '/phpunit-bootstrap.php';

$configFile = __DIR__ . '/cli-config.php';

require_once $configFile;

$cli = \Supra\Console\Application::getInstance();
$cli->setCatchExceptions(true);
//$cli->setHelperSet($helperSet);

$cli->addCommands(array(
	new \Supra\Tests\Controller\Pages\Fixture\PageFixtureCommand(),
	new \Supra\Database\Console\SchemaUpdateCommand(),
	new \Supra\Database\Console\SchemaCreateCommand(),
	new \Supra\Database\Console\SchemaDropCommand()
));

$cli->setHelperSet($helperSet);
$cli->run();
