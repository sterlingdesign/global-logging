## sterlingdesign/global-logging
 
### Overview

This package provides global functions for logging and de-couples from any and 
all PSR-3 implementations.  Instead of storing or injecting a PSR-3 logger interface in
each class where logging functionality is needed, the Logger is stored in a global,
single instance object that can be configured at startup/bootstrap globally.  

The global functions can be called by any function or class member (including static
class members).  If configured, the globally configured logger is used, otherwise
the native php `error_log()` function is used.  

For more about PSR-3, see https://www.php-fig.org/psr/psr-3/

### Installation

`composer require sterlingdesign/global-logging`

The installation includes a test file so that you can verify the installation
and also experiment with operation and configuration. The script is meant to 
be run from the command line, for example:

`php -f vendor/sterlingdesign/global-logging/test/test-global-logging-cli.php`

### Log Target Configuration

In the simplest case, the Log functions (see __Global Functions__ below) can be used 
__without__ configuring any 
PSR-3 log target.  If a PSR-3 logger has not been configured, the built-in PHP 
function `error_log($message, 0)` is used to send the message to PHP's system
logger (see https://www.php.net/manual/en/function.error-log).

<blockquote>
<b>NOTE</b>: all LogXXXX calls are sent ONLY to the php system log unless you configure
another PSR-3 Logger (see below). If your
application requires Email or SMS notification of critical errors, configure one
of the many logger's available with that capability, such as monolog
</blockquote>

If your application uses a PSR-3 compliant logging facility, such as 'monolog', you can
create an instance of that logger and set that object as the global target.
If a PSR-3 logger has been configured, that logger is used by all LogXXXX 
functions _instead of_ the native PHP `error_log()` function.

*your_bootstrap_file.php*

    <?php

    use Sterling\LogTarget;
    use Psr\Log\LogLevel;

    if(class_exists('\\Monolog\\Logger') && class_exists('\\Monolog\\Handler\\StreamHandler'))
        {
        // If Using a PSR-3 Logger, configure it and tell LogTarget to use it:
        $log = new \Monolog\Logger('name');
        $log->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/testing_monolog.log', Logger::WARNING));
        LogTarget::getInstance()->setLogger($log);
        }
    else
        {
        // The following option only applies if you are not using a logger:  
        // If you don't have a way to send debug logs back to the client, 
        // you may want to write all LogXXXX calls to the PHP system log.
        // CAUTION: This could potentially result in large amounts of log data
        LogTarget::getInstance()->setIgnoreLogLevels([]);
        // Or, maybe you would like to ignore all log calls below Warning
        // to reduce the amount of data written to the PHP system log:
        LogTarget::getInstance()->setIgnoreLogLevels([LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG]);
        }
    
    // - Configure other LogTarget Options:    
    // enable automatic context generation for all LogLevels for more detail:
    LogTarget::getInstance()->setAutomaticContextGenerationLevels(LogTarget::ALL_LEVELS);
    // disable storing log calls in memory if you don't need to look at them or your PSR-3 logger already does this
    LogTarget::getInstance()->setStoreInMemory(false);

*some_application_file.php*

    // add records to the log: These functions are supplied by this package:
    LogWarning('Foo');
    LogError('Bar');
    LogDebug("Testing!");

For a working example, see the included test script that should be installed 
at `vendor\sterlingdesign\global-logging\test\test-global-logging-cli.php`

### Global Functions

These functions are supplied by this package and are loaded by the autoloader 
into the top-level, global, namespace.

    LogEmergency(mixed $item, array $context = array());
    
    LogAlert(mixed $item, array $context = array());
    
    LogCritical(mixed $item, array $context = array());
    
    LogError(mixed $item, array $context = array());
    
    LogWarning(mixed $item, array $context = array());
    
    LogNotice(mixed $item, array $context = array());
    
    LogInfo(mixed $item, array $context = array());
    
    LogDebug(mixed $item, array $context = array());
    
    LogAtLevel($level, mixed $item, array $context = array());
    
    // For LogAtLevel, the $level parameter should be one of the \Psr\Log\LogLevel constants.
    // If it is not one of the defined values, logging will default 
    // to \Psr\Log\LogLevel::ERROR

The global functions are simple wrappers for calling the LogAtLevel function
on the global, single instance of \Sterling\LogTarget.  One could also use 
the LogTarget directly, for example:

    \Sterling\LogTarget::getInstance()->LogAtLevel(\Psr\Log\LogLevel::DEBUG, "Testing!", []);

### Additional Functionality

#### In-Memory Log Store
In addition to forwarding LogXXXX function calls to a PSR-3 Logger (if configured) 
or php log (if no PSR-3 Logger is configured),
the LogTarget class also stores the current requests' Log call Information in
an in-memory array.  This is useful for dumping current request log info
back to the client when debugging.

By default, each call to the LogXXXX functions for the current request is 
stored in an array of arrays.  For example,
to get the array of stored Log calls, you could do the following:

    require_once "path/to/your/autoload.php";
    // Optionally configure any Psr\Log\LoggerInterface you are using
    // \Sterling\LogTarget::getInstance()->setLogger($oMyLogger);
    
    LogDebug("Testing!");
    echo \Sterling\LogTarget::getInstance()->getLogFormatted();
    
If one of your log handlers already has this functionality, you can
disable the Sterling\LogTarget store in memory by calling

    \Sterling\LogTarget::getInstance()->setStoreInMemory(false);

Or you may wish to disable/enable In-Memory storage of Log calls
Dynamically depending on if you are debugging or not:

    \Sterling\LogTarget::getInstance()->setStoreInMemory(IsDebug());
    
#### Automatic Context Generation

The PSR-3 standard provides for supplying a context to the Logger::log
functions.  These contexts can be automatically generated in the
form of a debug_backtrace if not explicitly supplied to the LogXXXX functions.

You can control which LogLevel's, if any, get automatically generated
backtraces using the LogTarget::setAutomaticContextGenerationLevels()
function.  The argument should be an array specifying which LogLevels 
should have contexts generated automatically. To disable automatic context 
generation, pass an empty array.

### Roadmap

The current implementation provides a basic php log file output without 
any further work.  In the future, depending on demand, it would be possible
to add formatting functionality so that when there are no PSR-3 Logger
objects, the php log file output can be customized.

The immediate goal is to correct any glaring errors in the existing implementation,
then maintain the package for compatibility with the PSR-3 standard
and also compatibility with the native php function error_log.

Breaking changes are possible if anyone suggests a good idea, and would
be introduced in a future major version release.

Contributions are welcome.