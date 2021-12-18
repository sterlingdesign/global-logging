## sterlingdesign/global-logging
 
### Overview

This package provides global functions for logging and de-couples from any and 
all PSR-3 implementations.  Instead of storing or injecting a PSR-3 logger interface in
each class where logging functionality is needed, the Logger is stored in a global,
single instance object that can be configured at startup/bootstrap globally.  

The global functions can be called by any function or class member (including static
class members).  If configured, the globally configured logger is used, otherwise
the native php `error_log()` function is used.  

### Installation

`composer require sterlingdesign/global-logging`

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
    
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    use Sterling\LogTarget;

    // create a log channel
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));
    
    // \Sterling\LogTarget is a single instance class that implements the
    // \Psr\Log\LoggerAwareInterface
    LogTarget::getInstance()->setLogger($log);

    // Optionally, configure other LogTarget properties:
    // in case you don't have a way to send debug logs back to client (use caution, may fill up your system log)
    LogTarget::getInstance()->setSendDebugToSyslog(true); 
    // disable automatic context generation for all LogLevels (not reccomended)
    LogTarget::getInstance()->setAutomaticContextGenerationLevels([]);
    // disable storing log calls in memory if you don't need to look at them or your PSR-3 logger already does this
    LogTarget::getInstance()->setStoreInMemory(false);

*some_application_file.php*

    // add records to the log: These functions are supplied by this package:
    LogWarning('Foo');
    LogError('Bar');
    LogDebug("Testing!");

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

In addition to forwarding LogXXXX function calls to a PSR-3 Logger (if configured) 
or php log (if no PSR-3 Logger is configured),
the LogTarget class also stores the current requests' Log call Information in
an in-memory array.  This is useful for dumping current request log info
back to the client when debugging.

By default, each call to the LogXXXX functions for the current request is 
stored in an array of arrays based on the Psr\Log\LogLevel.  For example,
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