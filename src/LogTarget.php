<?php
declare(strict_types=1);
namespace Sterling;

use parallel\Sync\Error;
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
  private $m_bStoreInMemory = true;

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
//-------------------------------------------------------------------------------------
  public function setStoreInMemory(bool $bStoreLogCalls = true)
  {
    $this->m_bStoreInMemory = $bStoreLogCalls;
  }
//-------------------------------------------------------------------------------------------------
  public function LogAtLevel($level, $item, array $context, bool $bRemoveCallerFromContext = false)
  {
    //static $arLevelsNeedingTrace = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR];
    static $arLevelsNeedingTrace = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::DEBUG];
    $level = self::normalizeLevel($level);
    $message = self::getMessageFromItem($item);
    if(count($context) == 0 && array_search($level, $arLevelsNeedingTrace) !== false)
      $context = self::getContextFromItem($item, $bRemoveCallerFromContext);
    $strContext = "";
    if($this->m_bStoreInMemory || !is_object($this->m_oLogger))
      $strContext = self::formatContext($context);
    if($this->m_bStoreInMemory)
      $this->storeLogEntry($level, $message, $strContext);
    if(is_object($this->m_oLogger))
      $this->m_oLogger->log($level, $message, $context);
    else
      self::sendToPhpLog($level, $message, $strContext);
  }
//-------------------------------------------------------------------------------------------------
  private function storeLogEntry(string $level, string $message, string $context)
  {
    if(function_exists('hrtime'))
      {
      $timeType = 'hrtime';
      $timeVal = hrtime();
      }
    else if(function_exists('microtime'))
      {
      $timeType = 'microtime';
      $timeVal = microtime();
      }
    else
      {
      $timeType = 'time';
      $timeVal = time();
      }
    array_push($this->m_arLog, [$timeType=>$timeVal,"level"=>$level,"message"=>$message, "context"=>$context]);
  }
//-------------------------------------------------------------------------------------------------
  public function getLogRaw() : array
  {
    return $this->m_arLog;
  }
//-------------------------------------------------------------------------------------------------
  public function getLogFormatted() : string
  {
    $strLog = "";
    foreach($this->m_arLog as $logItem)
      {
      if(!is_array($logItem))
        continue;
      $timeVal = "";
      if(isset($logItem['hrtime']))
        $timeVal = implode(',', $logItem['hrtime']);
      else if(isset($logItem['microtime']))
        $timeVal = strval($logItem['microtime']);
      else if(isset($logItem['time']))
        $timeVal = strval($logItem['time']);
      $strLog .= "[{$timeVal}]";
      $strLog .= "[" . strtoupper($logItem['level']) . "]";
      $strLog .= $logItem['message'];
      $strLog .= $logItem['context'];
      $strLog .= PHP_EOL;
      }
    return $strLog;
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
        $e = new \Exception(); // we will create an error and get the stack trace from that
        $context = $e->getTrace();
        // remove this function, LogAtLevel, and one more if $bRemoveCallerFromContext is true
        $context = array_splice($context, ($bRemoveCallerFromContext ? 3 : 2));
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
  protected static function sendToPhpLog($level, string $message, string $strContext)
  {
    $level = strtoupper($level);
    if(strlen($message) == 0)
      $message = "(NO MESSAGE SUPPLIED)";
    error_log("[{$level}] {$message}{$strContext}", 0);
  }
//-------------------------------------------------------------------------------------------------
  static private function formatContext(array $context) : string
  {
  $strContext = count($context) ? PHP_EOL : "";
    foreach($context as $arItem)
      $strContext .= self::formatContextItem($arItem) . PHP_EOL;
    return $strContext;
  }
//-------------------------------------------------------------------------------------------------
  static private function formatContextItem($item) : string
  {
    if(is_array($item) && (isset($item["file"]) || isset($item['function'])))
      {
      $strItem = " ";
      if(isset($item['file']))
        $strItem .= "{$item['file']}:";
      if(isset($item['line']))
        $strItem .= "{$item['line']}:";
      if(isset($item['class']))
        $strItem .= $item['class'] . "::";
      if(isset($item['function']))
        {
        $strItem .= $item['function'] . "(";
        if(isset($item['args']))
          $strItem .= self::formatContextItemArgs($item['args']);
        $strItem .= ")";
        }
      }
    else
      $strItem = self::formatContextItemUnknown($item);

    return $strItem;
  }
//-------------------------------------------------------------------------------------------------
static private function formatContextItemArgs($arArgs) : string
{
  $s = "";
  if(is_array($arArgs))
    {
    $strArgs = "";
    foreach($arArgs as $key => $val)
      {
      $strArgs .= $s;
      if(is_object($val))
        $strArgs .= get_class($val);
      else if(is_array($val))
        $strArgs .= self::dumpArgArray($val);
      else
        $strArgs .= $val;
      $s = ", ";
      }
    }
  else if(is_object($arArgs))
    {
    $strArgs = "class " . get_class($arArgs);
    }
  else
    {
    $strArgs = gettype($arArgs);
    }
  return $strArgs;
}
//-------------------------------------------------------------------------------------------------
 private static function dumpArgArray(array $arVal) : string
 {
   $str = "[";
   $s = "";
   foreach($arVal as $key=>$val)
     {
     $str .= $s;
     if(is_string($val))
       {
       $str .= "\"";
       $str .= substr($val, 0, 64);
       if(strlen($str) > 64)
         $str .= "...";
       $str .= "\"";
       }
     else if(is_object($val))
       $str .= get_class($val);
     else
       $str .= gettype($val);
     $s = ",";
     }
   $str .= "]";
   return $str;
 }
//-------------------------------------------------------------------------------------------------
  private static function formatContextItemUnknown($item) : string
  {
    if(is_object($item))
      $strItem = " UNEXPECTED CONTEXT OBJECT: " . get_class($item);
    else
      {
      $strItem = " UNEXPECTED CONTEXT ITEM: ";
      $strItem .= gettype($item);
      if(is_array($item))
        {
        $strItem .= " = [";
        $s = "";
        foreach($item as $key=>$val)
          {
          $strItem .= $s . "'{$key}'=>";
          $strItem .= gettype($val);
          $s = ",";
          }
        $strItem .= "]";
        }
      else
        {
        $strItem .= strval($item);
        }
      }

    return $strItem;
  }
}