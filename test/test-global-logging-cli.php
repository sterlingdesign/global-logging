<?php
require_once __DIR__ . "/../../../autoload.php";
use Sterling\LogTarget;
use Psr\Log\LogLevel;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');

// PHP error_log
//
// In a normal website environment, the php error_log would usually be set.
// "If this directive is not set, errors are sent to the SAPI error logger.
// For example, it is an error log in Apache or stderr in CLI."
// For testing, uncomment the following ini_set line to have PHP create the
// specified file.  The file will be created relative to the
// current working directory.
//ini_set('error_log','testing_php_error.log');

// For reference, show where are errors are logged:
echo "PHP ini_get('error_log') = " . ini_get('error_log') . PHP_EOL;

// Configure the global instance of LogTarget:
// \Sterling\LogTarget is a single instance class that implements the \Psr\Log\LoggerAwareInterface

// if monolog is installed, set it up as the LogTarget
if(class_exists('\\Monolog\\Logger') && class_exists('\\Monolog\\Handler\\StreamHandler'))
  {
  $log = new \Monolog\Logger('name');
  $log->pushHandler(new \Monolog\Handler\StreamHandler('testing_monolog.log', Logger::WARNING));
  LogTarget::getInstance()->setLogger($log);
  }

// Optionally, configure other LogTarget properties:

// In case you don't have a way to send debug logs back to client (use caution, may fill up your system log)
//LogTarget::getInstance()->setSendDebugToSyslog(true);

// You can Disable automatic context generation for all LogLevels
//LogTarget::getInstance()->setAutomaticContextGenerationLevels([]);
// Or configure which levels get automatic context generation
//LogTarget::getInstance()->setAutomaticContextGenerationLevels([LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::DEBUG]);

// You can disable storing log calls in memory if you don't need to look at them or
// your PSR-3 logger already does this. The default is to store all calls passed
// through the LogXXXX functions.
// For these tests, we will store them in memory
//LogTarget::getInstance()->setStoreInMemory(false);

echo "TESTING THE LogXXXX FUNCTIONS..." . PHP_EOL;

run_tests();

echo PHP_EOL . "CONTENTS OF THE LogTarget's IN MEMORY LOG:" . PHP_EOL;
echo LogTarget::getInstance()->getLogFormatted();

function run_tests()
{
// If the first parameter of any of the LogXXXX functions is a string, that string will be used
// as the message:
  LogDebug("Testing LogDebug");
  LogWarning("Testing LogWarning");
  LogError("Testing LogError");

// If the first parameter to any of the LogXXXX functions is a Throwable (Exception) object, that objects
// message and context are used
  try
    {
    $oNone = new \NonExistant\ClassObj();
    $oNone->doSomething();
    }
  catch(\Throwable $throwable)
    {
    LogError($throwable);
    }

// You can also specify a context to the LogXXXX functions as the second parameter
  LogDebug("Test LogDebug WITH a context", debug_backtrace());

// If you pass an array to the LogXXXX functions, the message displayed will consist of the
//  first level keys and value types of the array
  LogDebug(["Some Text", 42, ["child array"]]);

  // LibXMLError is a special type not derived from \Throwable
  if(class_exists("\\DOMDocument"))
    {
    $oDoc = new DOMDocument();
    if(false == $oDoc->load("Non-Existent-File.bad"))
      LogError(libxml_get_last_error());
    }
}

