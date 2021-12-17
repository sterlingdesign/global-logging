<?php
declare(strict_types=1);

use Psr\Log\LogLevel;
use Sterling\LogTarget;

//-------------------------------------------------------------------------------------------------
function LogEmergency($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::EMERGENCY, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogAlert($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::ALERT, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogCritical($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::CRITICAL, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogError($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::ERROR, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogWarning($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::WARNING, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogNotice($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::NOTICE, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogInfo($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::INFO, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogDebug($item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel(LogLevel::DEBUG, $item, $context, true);
};
//-------------------------------------------------------------------------------------------------
function LogAtLevel($level, $item, array $context = array())
{
  LogTarget::getInstance()->LogAtLevel($level, $item, $context, true);
};
