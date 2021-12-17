<?php
declare(strict_types=1);
namespace Sterling;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogTarget implements LoggerAwareInterface
{
  private static $instance = null;
  private function __clone() { }
  public function __wakeup() { throw new \Exception("LogTarget is unserializable"); }
  private function __construct() { }
  /** @var LoggerInterface  */
  private $m_oLogger;
  private $m_arLog;

//-------------------------------------------------------------------------------------
  public static function getInstance() : LogTarget
  {
    if(is_null(self::$instance))
      {
      self::$instance = new LogTarget();
      self::$instance->Initialize();
      }
    return self::$instance;
  }
//-------------------------------------------------------------------------------------
  private function Initialize()
  {
    $this->m_oLogger = null;
    $this->m_arLog = array();
  }
//-------------------------------------------------------------------------------------
  public function setLogger(LoggerInterface $logger)
  {
    $this->m_oLogger = $logger;
  }
//-------------------------------------------------------------------------------------------------
  public function LogAtLevel($level, $item, array $context, bool $bRemoveCallerFromContext = false)
  {
    static $arLevelsNeedingTrace = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR];
    $level = self::normalizeLevel($level);
    $message = self::getMessageFromItem($item);
    if(count($context) == 0 && array_search($level, $arLevelsNeedingTrace) !== false)
      $context = self::getContextFromItem($item, $bRemoveCallerFromContext);
    $this->storeLog($level, $message, $context);
    if(is_object($this->m_oLogger))
      $this->m_oLogger->log($level, $message, $context);
    else
      self::sendToPhpLog($level, $message, $context);
  }
//-------------------------------------------------------------------------------------------------
  private function storeLog(string $level, string $message, array $context)
  {
    $level = strtoupper($level);
    if(!isset($this->m_arLog[$level]))
      $this->m_arLog[$level] = array();
    array_push($this->m_arLog[$level], ["message"=>$message, "context"=>$context]);
  }
//-------------------------------------------------------------------------------------------------
  public function getLog() : array
  {
    return $this->m_arLog;
  }
//-------------------------------------------------------------------------------------------------
  protected static function getMessageFromItem($E) : string
  {
    try
      {
      // THE MESSAGE
      if(is_object($E) && get_class($E) != "LibXMLError" && is_a($E, "\Throwable"))
        {
        $strMsgFull = $E->getMessage();
        }
      else
        {
        if(is_object($E) && get_class($E) == "LibXMLError")
          $strMsgFull = $E->message;
        else if(is_object($E))
          $strMsgFull = "object class:" . get_class($E);
        else if(is_string($E))
          $strMsgFull = $E;
        else
          $strMsgFull = print_r($E, true);
        }
      }
    catch(\Throwable $throwable)
      {
      $strMsgFull = "Error thrown by ";
      if(is_object($E))
        $strMsgFull .= "object class:" . get_class($E);
      else
        $strMsgFull .= gettype($E);
      $strMsgFull .= " while logging message";
      }

    return $strMsgFull;
  }
//-------------------------------------------------------------------------------------------------
  protected static function getContextFromItem($item, bool $bRemoveCallerFromContext) : array
  {
    try
      {
      if(is_object($item) && is_a($item, "\Throwable") && method_exists($item, "getTrace"))
        $context = $item->getTrace();
      else
        {
        // we will create an error and get the stack trace from that
        $e = new \Exception();
        $context = $e->getTrace();
        // remove this function, LogAtLevel, and one more if $bRemoveCallerFromContext is true
        $context = array_splice($context, 0, ($bRemoveCallerFromContext ? 3 : 2));
        }
      }
    catch(\Throwable $throwable)
      {
      $context = array();
      }

    return $context;
  }
//-------------------------------------------------------------------------------------------------
  protected static function normalizeLevel($level) : string
  {
    static $arLevels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::DEBUG, LogLevel::INFO, LogLevel::NOTICE, LogLevel::WARNING, LogLevel::ERROR];
    // the Psr\Log\LogLevel class provides the constants as strings right now,
    // but...in the future they may be integers (enum's)
    if(is_int(LogLevel::ERROR))
      {
      if(!is_int($level) || array_search($level, $arLevels))
        $level = LogLevel::ERROR;
      }
    else if(!is_string($level) || array_search($level, $arLevels) === false)
      $level = LogLevel::ERROR;
    return $level;
  }
//-------------------------------------------------------------------------------------------------
  protected static function sendToPhpLog($level, string $message, array $context)
  {
    $level = strtoupper($level);

    $strContext = "";
    if(count($context))
      {
      $strContext = PHP_EOL . "CONTEXT:" . PHP_EOL;
      $strContext .= print_r($context, true);
      }
    if(strlen($message) == 0)
      $message = "(MESSAGE NOT SUPPLIED)";
    error_log("[{$level}] {$message}{$strContext}", 0);
  }
}