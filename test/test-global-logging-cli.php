<?php
require_once __DIR__ . "/../../../autoload.php";
use Sterling\LogTarget;
use Psr\Log\LogLevel;

//-------------------------------------------------------------------------------------------------
// - Configure the PHP Logging behavior for testing
ini_set('error_reporting', E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('html_errors', '0');

// - PHP error_log
//
// In a normal website environment, the php error_log would usually be set.
// "If this directive is not set, errors are sent to the SAPI error logger.
// For example, it is an error log in Apache or stderr in CLI."
// For testing, uncomment the following ini_set line to have PHP create the
// specified file.  The file will be created relative to the
// current working directory if an absolute path is not provided.
ini_set('error_log', __DIR__ . '/testing_php_error.log');

//-------------------------------------------------------------------------------------------------
// - Configure the global instance of LogTarget:
// \Sterling\LogTarget is a single instance class that implements
// the \Psr\Log\LoggerAwareInterface

// --- If Using a PSR-3 Logger, configure it and tell LogTarget to use it:
if(class_exists('\\Monolog\\Logger') && class_exists('\\Monolog\\Handler\\StreamHandler'))
  {
  $log = new \Monolog\Logger('name');
  $log->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/testing_monolog.log', Logger::WARNING));
  LogTarget::getInstance()->setLogger($log);
  }
else
  {
  // When using native PHP system logging, you can configure which LogLevel's are
  // NOT written to the log file:
  // Write all LogXXXX calls to the system log:
  LogTarget::getInstance()->setIgnoreLogLevels([]);
  // The default is to only ignore LogDebug - all other log levels are written
  LogTarget::getInstance()->setIgnoreLogLevels([LogLevel::DEBUG]);
  }

// - Optionally, configure other LogTarget properties:

// You can Disable automatic context generation for all LogLevels
LogTarget::getInstance()->setAutomaticContextGenerationLevels([]);
// Or Enable automatic context generation for all LogLevels
LogTarget::getInstance()->setAutomaticContextGenerationLevels(LogTarget::ALL_LEVELS);
// Or Enable automatic context generation for specific LogLevels
LogTarget::getInstance()->setAutomaticContextGenerationLevels([LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR]);

// You can disable storing log calls in memory if you don't need to look at them or
// your PSR-3 logger already does this. The default is to store all calls passed
// through the LogXXXX functions.
LogTarget::getInstance()->setStoreInMemory(false);
// For these tests, we will store them in memory and display the contents after testing
LogTarget::getInstance()->setStoreInMemory(true);

//-------------------------------------------------------------------------------------------------
// - For reference, show where PHP errors, warnings and calls to error_log() are sent:
echo "PHP is writing errors and warnings to ";
if(empty(ini_get('error_log')))
  {
  if(php_sapi_name() === 'cli')
     echo "the CLI STDERR";
  else
    echo "the SAPI error logger";
  }
else
  echo "the file " . ini_get('error_log');
echo PHP_EOL;

//-------------------------------------------------------------------------------------------------
// - Run some tests
echo "TESTING THE LogXXXX FUNCTIONS..." . PHP_EOL;
run_tests();
echo PHP_EOL . "CONTENTS OF THE LogTarget's IN MEMORY LOG:" . PHP_EOL;
echo LogTarget::getInstance()->getLogFormatted();

//-------------------------------------------------------------------------------------------------
function run_tests()
{
  // If the first parameter of any of the LogXXXX functions is a string,
  // that string will be used as the message:
  LogDebug("Testing LogDebug");
  LogWarning("Testing LogWarning");
  LogError("Testing LogError");

  // If the first parameter to any LogXXXX functions is a Throwable
  // object, that objects getMessage() function is used as the
  // logged message, and its getTrace() function is used as
  // the context (unless a context is explicitly supplied)
  try
    {
    $oNone = new \NonExistant\ClassObj();
    $oNone->doSomething();
    }
  catch(\Throwable $throwable)
    {
    LogError($throwable);
    }

  // If first parameter to any LogXXXX function is an array,
  // the message displayed will consist of the
  // keys and value types of the first level array elemements
  LogDebug(["Some Text", 42, ["child array"]]);

  // LibXMLError is a special type not derived from \Throwable
  if(class_exists("\\DOMDocument"))
    {
    $oDoc = new DOMDocument();
    if(false == $oDoc->load("Non-Existent-File.bad"))
      LogError(libxml_get_last_error());
    }

  // You can also specify a context to any LogXXXX functions
  LogDebug("Test LogDebug WITH a context", debug_backtrace());

  // Test Logging from inside a class:
  $oTestClass = new TestClass();
  $oTestClass->RunTests();
}

class TestClass
{
  public function RunTests()
  {
    try
      {
      $val = 4.0/0.0;
      }
    catch(\Throwable $throwable)
      {
      LogError($throwable);
      }

    LogWarning("A test warning from Test Class");
    LogCritical("Testing LogCritical from Test Class");
    LogInfo("Testing LogInfo from Test Class WITH CONTEXT", debug_backtrace());
    LogError("Testing LogError from Test Class");
    self::StaticTests();
  }
  public static function StaticTests()
  {
    LogError("Testing LogError from static TestClass function");
  }
}

