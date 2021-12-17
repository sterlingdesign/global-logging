## sterlingdesign/global-logging
 
### Overview

This package provides global functions for logging and de-couples from any and 
all PSR-3 implementations.  Instead of storing or injecting a PSR-3 logger interface in
each class where logging functionality is needed, the interface is stored in a global,
single instance object that can be configured at startup/bootstrap globally.  

The global functions can be called by any function or class object (including static
class functions), which in turn uses the globally configured logger.  

### Installation

`composer require sterlingdesign/global-logging`

### Log Target Configuration

In the simplest case, the Log functions (below) can be used __without__ configuring any 
PSR-3 log target.  If a PSR-3 logger has not been configured, the built-in PHP 
function `error_log($message, 0)` is used to send the message to PHP's system
logger (see https://www.php.net/manual/en/function.error-log).

If your application uses a PSR-3 compliant logging facility, such as 'monolog', you can
create an instance of that logger and set that object as the global target.
If a PSR-3 logger been configured, that logger is used by all LogXXXX functions.

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

*some_application_file.php*

    // add records to the log
    LogWarning('Foo');
    LogError('Bar');
    LogDebug("Testing!");

### Global Functions

The functions are loaded by the autoloader into the top-level, global, namespace.

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
