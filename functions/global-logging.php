<?php
declare(strict_types=1);

use Psr\Log\LogLevel;
use Sterling\LogTarget;

//-------------------------------------------------------------------------------------------------
function LogEmergency($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::EMERGENCY, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogAlert($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::ALERT, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogCritical($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::CRITICAL, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogError($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::ERROR, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogWarning($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::WARNING, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogNotice($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::NOTICE, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogInfo($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::INFO, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogDebug($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::DEBUG, $item, $context);
};
//-------------------------------------------------------------------------------------------------
function LogAtLevel($level, $item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel($level, $item, $context);
};
