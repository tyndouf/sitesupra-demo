<?php

namespace Supra\Log\Formatter;

//use Supra\Log\LogEvent;

/**
 * Audit log formatter
 */
class AuditLogFormatter extends SimpleFormatter
{
	
	const FORMAT = '[%time%] %level% %action% by %user% in %component% - %subject%';
	
	/**
	 * Configuration
	 * @var array
	 */
	protected static $defaultParameters = array(
		'format' => self::FORMAT,
		'timeFormat' => 'Y-m-d H:i:s',
	);
	
}