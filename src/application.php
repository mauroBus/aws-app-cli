#!/usr/bin/env php
<?php
//Composer autoload
require __DIR__ . '/../vendor/autoload.php';

use Devspark\Di\Container as DiContainer;
use Devspark\Console\Command\LogCommand;
use Symfony\Component\Console\Application;

//export DEVSPARK_CLI_HOME='/home/sarrubia/projects/devspark/aws_training/aws-app-cli'
defined('DEVSPARK_CLI_HOME') || define('DEVSPARK_CLI_HOME', (getenv('DEVSPARK_CLI_HOME') ? getenv('DEVSPARK_CLI_HOME') : '/opt/aws-app-cli/config'));

$ini_array = parse_ini_file(DEVSPARK_CLI_HOME."/config/cli.config.ini", true);

//Adding configuration.
$diContainer = DiContainer::getInstance();
$diContainer->set('configuration', $ini_array);

//Creating console app
$application = new Application('Devspark CLI App','1.0');
//Adding Log command
$application->add(new LogCommand());
//Running app.
$application->run();