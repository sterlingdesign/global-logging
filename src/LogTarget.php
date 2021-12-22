<?php
declare(strict_types=1);
namespace Sterling;

use parallel\Sync\Error;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class LogTarget implements LoggerAwareInterface
{
  const ALL_LEVELS = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];

  private static $instance = null;
  private function __clone() { }
  public function __wakeup() { throw new \Exception("LogTarget is unserializable"); }
  private function __construct() { }
  /** @var LoggerInterface  */
  private $m_oLogger;
  private $m_arLog;
  private $m_bStoreInMemory = true;
  private $m_arAutomaticContextLevels = array();
  //private $m_arIgnoreLogLevels = array();
  private $m_arIgnoreLogLevels = array();

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
    $this->setAutomaticContextGenerationLevels();
    $this->setIgnoreLogLevels();
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
//-------------------------------------------------------------------------------------
  public function setAutomaticContextGenerationLevels(array $arLevels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR])
  {
    // TO DO: we could validate the contents of the array
    $this->m_arAutomaticContextLevels = $arLevels;
  }
//-------------------------------------------------------------------------------------
  public function setIgnoreLogLevels(array $arLevels = [LogLevel::DEBUG])
  {
    $this->m_arIgnoreLogLevels = $arLevels;
  }
//-------------------------------------------------------------------------------------------------
  public function LogAtLevel($level, $item, array $context)
  {
    $level = self::normalizeLevel($level);
    $message = self::getMessageFromItem($item);
    if(count($context) == 0 && array_search($level, $this->m_arAutomaticContextLevels) !== false)
      $context = self::getContextFromItem($item);
    $strContext = "";
    // It would be nice to use the built-in Exception::getTraceAsString(),
    // however in order to follow the Psr-3 guidelines, we don't know where "$context" came from
    // and so we need to manually print that array.
    if($this->m_bStoreInMemory || !is_object($this->m_oLogger))
      $strContext = self::formatContext($context);
    // Note that we do not want to store the $context in memory here,
    // since it may contain object references: we don't want to prevent
    // those objects from being deleted from memory once this function call is done
    // and so we only store the formatted string
    if($this->m_bStoreInMemory)
      $this->storeLogEntry($level, $message, $strContext);
    // If a PSR-3 Logger was supplied, use that, otherwise send to the PHP system log
    if(is_object($this->m_oLogger))
      $this->m_oLogger->log($level, $message, $context);
    else if(array_search($level, $this->m_arIgnoreLogLevels) === false)
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
      if(is_object($E))
        {
        if(is_a($E, "\LibXMLError") && property_exists($E, "message"))
          $strMsgFull = trim($E->message);
        else if(is_a($E, "\Throwable") && method_exists($E, "getMessage"))
          $strMsgFull = $E->getMessage();
        else
          $strMsgFull = "object class:" . get_class($E);
        }
      else if(is_string($E))
        $strMsgFull = $E;
      else if(is_array($E))
        $strMsgFull = "Array: " . self::dumpUnkownArray($E);
      else
        $strMsgFull = gettype($E) . ": " . strval($E);
      }
    catch(\Throwable $throwable)
      {
      $strMsgFull = "Error thrown by ";
      if(is_object($E))
        $strMsgFull .= "object class:" . get_class($E);
      else
        $strMsgFull .= gettype($E);
      $strMsgFull .= " while getting error message";
      }

    return $strMsgFull;
  }
//-------------------------------------------------------------------------------------------------
  protected static function getContextFromItem($item) : array
  {
    try
      {
      if(is_object($item) && is_a($item, "\Throwable") && method_exists($item, "getTrace"))
        $context = $item->getTrace();
      else
        {
        //$e = new \Exception(); // we will create an error and get the stack trace from that
        //$context = $e->getTrace();
        $context = debug_backtrace();
        // remove this function and LogTarget::LogAtLevel from where this was called
        $context = array_splice($context, 2);
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
    // the Psr\Log\LogLevel class provides the constants as strings
    if(!is_string($level) || array_search($level, $arLevels) === false)
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
    $strContext = "";
    $i = 0;
    foreach($context as $arItem)
      $strContext .= PHP_EOL . self::formatContextItem($arItem, $i++);
    return $strContext;
  }
//-------------------------------------------------------------------------------------------------
  static private function formatContextItem($item, int $i) : string
  {
    $strItem = "  #{$i} ";
    if(is_array($item) && (isset($item["file"]) || isset($item['function'])))
      {
      if(isset($item['class']))
        $strItem .= $item['class'] . "::";
      if(isset($item['function']))
        {
        $strItem .= $item['function'] . "(";
        if(isset($item['args']))
          $strItem .= self::formatContextItemArgs($item['args']);
        $strItem .= ")";
        }

      if(isset($item['file']))
        {
        $strItem .= " [";
        $strItem .= "{$item['file']}:";
        if(isset($item['line']))
          $strItem .= "{$item['line']}";
        $strItem .= "]";
        }
      }
    else
      $strItem .= self::formatContextItemUnknown($item);

    // we want it on one line:
    return str_replace(["\n","\r","\t","  "],[" "," "," "," "],$strItem);
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
        $strArgs .= self::formatArgArray($val);
      else
        $strArgs .= self::dumpUnknownString($val);
      $s = ",";
      }
    }
  else if(is_object($arArgs))
    {
    $strArgs = get_class($arArgs);
    }
  else
    {
    $strArgs = gettype($arArgs);
    }
  return $strArgs;
}
//-------------------------------------------------------------------------------------------------
 private static function formatArgArray(array $arVal) : string
 {
   $str = "[";
   $s = "";
   foreach($arVal as $key=>$val)
     {
     $str .= $s;
     if(is_string($val))
       $str .= self::dumpUnknownString($val);
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
      $strItem .= " = ";
      if(is_array($item))
        $strItem .= self::dumpUnkownArray($item);
      else
        $strItem .= self::dumpUnknownString($item);
      }

    return $strItem;
  }
//-------------------------------------------------------------------------------------------------
  private static function dumpUnkownArray(array $item, int $iMaxLen = 64) : string
  {
    $s = "";
    $strItem = "[";
    foreach($item as $key=>$val)
      {
      $strItem .= $s;
      if(strlen($strItem) >= $iMaxLen)
        {
        $strItem .= "...";
        break;
        }
      if(is_int($key))
        $strItem .= $key;
      else
        $strItem .= self::dumpUnknownString($key);
      $strItem .= "=>";
      $strItem .= gettype($val);
      $s = ",";
      }
    $strItem .= "]";
    return $strItem;
  }
//-------------------------------------------------------------------------------------------------
  private static function dumpUnknownString($item, int $iMaxLen = 64) : string
  {
    $strVal = strval($item);
    $strItem = "\"";
    $strItem .= substr($strVal, 0, $iMaxLen);
    if(strlen($strVal) > $iMaxLen)
      $strItem .= "...";
    $strItem .= "\"";
    return $strItem;
  }
}