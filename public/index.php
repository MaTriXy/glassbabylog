<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Setup autoloading
require 'init_autoloader.php';

date_default_timezone_set('America/New_York');

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
