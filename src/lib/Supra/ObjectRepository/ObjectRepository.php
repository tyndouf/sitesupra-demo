<?php

namespace Supra\ObjectRepository;

use Doctrine\ORM\EntityManager;
use Supra\FileStorage\FileStorage;
use Supra\User\UserProvider;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Session\SessionNamespace;
use Supra\Session\SessionManager;
use Supra\Log\Log;
use Supra\Locale\LocaleManager;
use Supra\Mailer\Mailer;
use Supra\Authorization\AuthorizationProvider;
use Supra\Cms\ApplicationConfiguration;
use Supra\Event\EventManager;
use Solarium_Client;
use Supra\Configuration\Loader\IniConfigurationLoader;

/**
 * Object repository
 */
class ObjectRepository
{
	const DEFAULT_KEY = '';
	
	/**
	 * Used when binding the object to controller
	 * TODO: just an idea, not realized because of multiple methods which should be implemented
	 */
	const CONTROLLER_PREFIX = 'CONTROLLER/';
	
	const INTERFACE_LOGGER = 'Supra\Log\Writer\WriterAbstraction';
	const INTERFACE_FILE_STORAGE = 'Supra\FileStorage\FileStorage';
	const INTERFACE_USER_PROVIDER = 'Supra\User\UserProvider';
	const INTERFACE_ENTITY_MANAGER = 'Doctrine\ORM\EntityManager';
	const INTERFACE_SESSION_NAMESPACE_MANAGER = 'Supra\Session\SessionManager';		
	const INTERFACE_SESSION_NAMESPACE = 'Supra\Session\SessionNamespace';
	const INTERFACE_LOCALE_MANAGER = 'Supra\Locale\LocaleManager';
	const INTERFACE_MAILER = 'Supra\Mailer\Mailer';
	const INTERFACE_AUTHORIZATION_PROVIDER = 'Supra\Authorization\AuthorizationProvider';
	const INTERFACE_APPLICATION_CONFIGURATION = 'Supra\Cms\ApplicationConfiguration';
	const INTERFACE_SOLARIUM_CLIENT = '\Solarium_Client';
	const INTERFACE_EVENT_MANAGER = 'Supra\Event\EventManager';
	const INTERFACE_INI_CONFIGURATION = 'Supra\Configuration\Loader\IniConfigurationLoader';
	
	/**
	 * Object relation storage
	 *
	 * @var array
	 */
	protected static $objectBindings = array();
	
	/**
	 * Forced caller hierarchy
	 * @var array
	 */
	protected static $callerHierarchy = array();

	/**
	 * Called controller stack (id list, last added controller first)
	 * @var array
	 */
	protected static $controllerStack = array();
	
	/**
	 * Variable for checking if the added binding wouldn't change any previous 
	 * repository requests. Can be disabled by setting to null. This also stores
	 * the 5 debug backtrace steps for developer.
	 * FIXME: Should be disabled on production for  performance reasons.
	 * @var array
	 */
	protected static $lateBindingCheckCache = array();

	/**
	 * Marks beginning of the controller context,
	 * adds the controller ID to the call stack
	 * @param string $controllerId
	 */
	public static function beginControllerContext($controllerId)
	{
		array_unshift(self::$controllerStack, $controllerId);
	}
	
	/**
	 * Marks the end of the controller execution
	 * @param string $expectedControllerId
	 * @throws Exception\LogicException
	 */
	public static function endControllerContext($expectedControllerId)
	{
		$actualControllerId = array_shift(self::$controllerStack);
		
		if ($actualControllerId != $expectedControllerId) {
			
			$expectationString = null;
			
			if (empty($actualControllerId)) {
				$expectationString = "No controller";
			} else {
				$expectationString = "Controller '$actualControllerId'";
			}
			
			throw new Exception\LogicException("$expectationString was expected to be ended, but '$expectedControllerId' was passed");
		}
	}
	
	/**
	 * Shouldn't be called. Used by tests.
	 */
	public function resetControllerContext()
	{
		self::$controllerStack = array();
	}
	
	/**
	 * Normalizes caller, object is converted to the class name string
	 * @param mixed $caller
	 * @return string
	 */
	private static function normalizeCallerArgument($caller)
	{
		if (is_object($caller)) {
			$callerClass = get_class($caller);
			$callerHash = spl_object_hash($caller);
			$caller = $callerClass . '\\' . $callerHash;
		} elseif ( ! is_string($caller)) {
			throw new Exception\RuntimeException('Caller must be class instance or class name');
		} else {
			$caller = trim($caller, '\\');
		}
		
		return $caller;
	}
	
	/**
	 * Normalizes interface name argument
	 * @param string $interface
	 * @return string
	 * @throws Exception\RuntimeException if argument invalid
	 */
	private static function normalizeInterfaceArgument($interface)
	{
		if ( ! is_string($interface)) {
			throw new Exception\RuntimeException('Interface argument must be a string');
		}
		
		$interface = trim($interface, '\\');
		
		return $interface;
	}
	
	/**
	 * @return string
	 */
	protected static function generateLateBindingCheckTrace()
	{
		$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$resultArray = array();
		$i = 1;
		
		foreach ($debugBacktrace as $trace) {
			
			$trace = (array) $trace + array(
				'class' => null,
				'type' => null,
				'function' => null,
				'line' => null,
				'file' => null,
			);
			
			if ($trace['class'] == __CLASS__) {
				continue;
			}
			
			$resultArray[] = "#$i: {$trace['class']}{$trace['type']}{$trace['function']}() in {$trace['file']}:{$trace['line']}";
			if ($i++ > 5) {
				break;
			}
		}
		
		$resultString = implode("\n", $resultArray);

		return $resultString;
	}
	
	/**
	 * @see self::$lateBindingCheckCache
	 * @param string $caller
	 * @param string $interface
	 */
	protected static function checkLateBinding($caller, $interface = null)
	{
		if (empty(static::$lateBindingCheckCache)) {
			return;
		}
		
		$interfaces = null;
		
		if (is_null($interface)) {
			$interfaces = array_keys(static::$lateBindingCheckCache);
		} else {
			$interfaces = (array) $interface;
		}
		
		foreach ($interfaces as $interface) {
		
			if ( ! isset(static::$lateBindingCheckCache[$interface])) {
				continue;
			}
			
			// Ignore the logger
			if ($interface == self::INTERFACE_LOGGER) {
				continue;
			}
			
			foreach (static::$lateBindingCheckCache[$interface] as $callerTest => $getterTrace) {

				if (self::isParentCaller($callerTest, $caller)) {
					self::getLogger(__CLASS__)
							->warn("Object repository binding of '$interface' was already requested for '$callerTest' before, '$caller' binding changed now.\n"
									. "HINT: Switch debugging on to see more information on this issue.");
					self::getLogger(__CLASS__)
							->debug("Got by trace:\n", $getterTrace);
					self::getLogger(__CLASS__)
							->debug("Checked by trace:\n", self::generateLateBindingCheckTrace());
					break;
				}
			}
		}
	}
	
	/**
	 * Internal relation setter
	 *
	 * @param string $callerClass
	 * @param object $object
	 * @param string $interface 
	 * @throws Exception\RuntimeException
	 */
	protected static function addBinding($caller, $object, $interface)
	{
		$caller = self::normalizeCallerArgument($caller);
		$interface = self::normalizeInterfaceArgument($interface);
		
		self::checkLateBinding($caller, $interface);
	
		if ( ! is_object($object)) {
			throw new Exception\RuntimeException('Object argument must be an object');
		}
		
		if ( ! is_a($object, $interface)) {
			throw new Exception\RuntimeException('Object must be an instance of interface class or must extend it');
		}

		self::$objectBindings[$interface][$caller] = $object;
	}
	
	/**
	 * Checks if caller is the child of the parent caller
	 * @param mixed $child
	 * @param mixed $parent
	 * @return boolean
	 */
	public static function isParentCaller($child, $parent)
	{
		$child = self::normalizeCallerArgument($child);
		$parent = self::normalizeCallerArgument($parent);
		
		while ( ! is_null($child)) {
			if ($child === $parent) {
				return true;
			}
			
			$visited = array();
			$child = self::getParentCaller($child, $visited);
		}
		
		return false;
	}
	
	/**
	 * Find object by exact namespace/classname
	 * @param string $caller
	 * @param string $interface
	 * @return object
	 */
	private static function findObject($caller, $interface)
	{
		if (isset(self::$objectBindings[$interface][$caller])) {
			return self::$objectBindings[$interface][$caller];
		}
	}

	/**
	 * Find object by namespace or it's parent namespaces
	 * @param string $caller
	 * @param string $interface 
	 */
	private static function findNearestObject($caller, $interface)
	{
		$object = null;
		$visited = array();
		
		do {
			$object = self::findObject($caller, $interface);
			$caller = self::getParentCaller($caller, $visited);
		} while (is_null($object) && ! is_null($caller));
		
		return $object;
	}
	
	/**
	 * Force caller object hierarchy
	 * @param mixed $child
	 * @param mixed $parent
	 * @param boolean $overwrite
	 */
	public static function setCallerParent($child, $parent, $overwrite = false)
	{
		// Shortcut variable
		$ch = &self::$callerHierarchy;
		
		$child = self::normalizeCallerArgument($child);
		$parent = self::normalizeCallerArgument($parent);
		
		self::checkLateBinding($child);
		
		if (( ! $overwrite) && isset($ch[$child]) && ($ch[$child] !== $parent)) {
			throw new Exception\RuntimeException("Caller $child parent already declared");
		}
		
		$ch[$child] = $parent;
	}
	
	/**
	 * Loads parent caller name for the current caller
	 * @param string $caller
	 * @param array $visited
	 * @return string
	 * @throws Exception\RuntimeException if infinite cycle defined in the hierarchy definition
	 */
	private static function getParentCaller($caller, array &$visited)
	{
		if ($caller === self::DEFAULT_KEY) {
			return null;
		}
		
		// Try parent namespace
		$backslashPos = strrpos($caller, "\\");
		$parentCaller = null;

		if (isset(self::$callerHierarchy[$caller])) {
			
			// Visited nodes are checked and registered only here, 
			// other strategies are safe
			if (in_array($caller, $visited)) {
				throw new Exception\RuntimeException("Loop detected in caller hierarchy");
			}
			$visited[] = $caller;
			
			$parentCaller = self::$callerHierarchy[$caller];
		} elseif ($backslashPos !== false) {
			$parentCaller = substr($caller, 0, $backslashPos);
		} else {
			$parentCaller = self::DEFAULT_KEY;
		}

		return $parentCaller;
	}

	/**
	 * Get object of specified interface assigned to caller class
	 *
	 * @param mixed $callerClass
	 * @param string $interface
	 * @return object
	 * @throws Exception\RuntimeException
	 */
	public static function getObject($caller, $interface)
	{
		$interface = self::normalizeInterfaceArgument($interface);
		
		// 1. Try matching any controller from the execution list
		foreach (self::$controllerStack as $controllerId) {
			$controllerCaller = $controllerId;
			// @see self::CONTROLLER_PREFIX
//			$controllerCaller = self::CONTROLLER_PREFIX . $controllerId;
			$object = self::findObject($controllerCaller, $interface);
			
			if ( ! is_null($object)) {
				return $object;
			}
		}
		
		// 2. If not found, try matching nearest defined object by caller
		$caller = self::normalizeCallerArgument($caller);
		
		// @see self::$lateBindingCheckCache
		if (isset(static::$lateBindingCheckCache)) {
			static::$lateBindingCheckCache[$interface][$caller] = 
					self::generateLateBindingCheckTrace();
		}
		
		$object = self::findNearestObject($caller, $interface);

		return $object;
	}

	/**
	 * Assign object of its own class to caller class
	 *
	 * @param mixed $caller
	 * @param object $object 
	 * @param string $interface
	 */
	public static function setObject($caller, $object, $interface)
	{
		self::addBinding($caller, $object, $interface);
	}
	
	/**
	 * Set default assigned object of its class
	 *
	 * @param mixed $object 
	 * @param string $interface
	 */
	public static function setDefaultObject($object, $interface)
	{
		self::addBinding(self::DEFAULT_KEY, $object, $interface);
	}
	
	/**
	 * Get assigned logger
	 *
	 * @param mixed $caller
	 * @return WriterAbstraction
	 */
	public static function getLogger($caller)
	{
		$logger = self::getObject($caller, self::INTERFACE_LOGGER);

		// Create bootstrap logger in case of missing logger
		if (empty($logger)) {
			$logger = Log::getBootstrapLogger();
		}

		return $logger;
	}

	/**
	 * Assign logger instance to caller class
	 *
	 * @param mixed $caller
	 * @param WriterAbstraction $object 
	 */
	public static function setLogger($caller, WriterAbstraction $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_LOGGER);
	}

	/**
	 * Set default logger
	 *
	 * @param WriterAbstraction $object
	 */
	public static function setDefaultLogger(WriterAbstraction $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_LOGGER);
	}

	/**
	 * Get entity manager assigned to caller class
	 *
	 * @param mixed $caller
	 * @return EntityManager
	 */
	public static function getEntityManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Assign entity manager instance to caller class
	 *
	 * @param mixed $caller
	 * @param EntityManager $object 
	 */
	public static function setEntityManager($caller, EntityManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Set default entity manager
	 *
	 * @param EntityManager $object 
	 */
	public static function setDefaultEntityManager(EntityManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_ENTITY_MANAGER);
	}

	/**
	 * Get assigned file storage
	 *
	 * @param mixed $caller
	 * @return FileStorage
	 */
	public static function getFileStorage($caller)
	{
		return self::getObject($caller, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Assign file storage instance to caller class
	 *
	 * @param mixed $caller
	 * @param FileStorage $object 
	 */
	public static function setFileStorage($caller, FileStorage $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Set default file storage
	 *
	 * @param FileStorage $object 
	 */
	public static function setDefaultFileStorage(FileStorage $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_FILE_STORAGE);
	}

	/**
	 * Get assigned session namespace
	 *
	 * @param mixed $caller
	 * @return SessionManager
	 */
	public static function getSessionManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Assign session manager to caller class
	 *
	 * @param mixed $caller
	 * @param SessionManager $object 
	 */
	public static function setSessionManager($caller, SessionManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Set default session manager
	 *
	 * @param SessionManager $object 
	 */
	public static function setDefaultSessionManager(SessionManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_SESSION_NAMESPACE_MANAGER);
	}

	/**
	 * Get assigned user provider
	 *
	 * @param mixed $caller
	 * @return UserProvider
	 */
	public static function getUserProvider($caller)
	{
		return self::getObject($caller, self::INTERFACE_USER_PROVIDER);
	}

	/**
	 * Assign user provider instance to caller class
	 *
	 * @param mixed $caller
	 * @param UserProvider $object 
	 */
	public static function setUserProvider($caller, UserProvider $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_USER_PROVIDER);
	}
	
	/**
	 * Set default user provider
	 *
	 * @param UserProvider $object 
	 */
	public static function setDefaultUserProvider(UserProvider $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_USER_PROVIDER);
	}
	
	/**
	 * Get assigned locale manager
	 *
	 * @param mixed $caller
	 * @return LocaleManager
	 */
	public static function getLocaleManager($caller)
	{
		return self::getObject($caller, self::INTERFACE_LOCALE_MANAGER);
	}

	/**
	 * Assign locale manager instance to caller class
	 *
	 * @param mixed $caller
	 * @param LocaleManager $object 
	 */
	public static function setLocaleManager($caller, LocaleManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_LOCALE_MANAGER);
	}
	
	/**
	 * Set default locale manager
	 *
	 * @param LocaleManager $object 
	 */
	public static function setDefaultLocaleManager(LocaleManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_LOCALE_MANAGER);
	}

	/**
	 * Assign mailer instance to caller class
	 *
	 * @param mixed $caller
	 * @param Mailer $object
	 */
	public static function setMailer($caller, Mailer $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_MAILER);
	}

	/**
	 * Set default mailer
	 *
	 * @param Mailer $object 
	 */
	public static function setDefaultMailer(Mailer $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_MAILER);
	}

	/**
	 * Get assigned mailer
	 *
	 * @param mixed $caller
	 * @return Mailer
	 */
	public static function getMailer($caller)
	{
		return self::getObject($caller, self::INTERFACE_MAILER);
	}
	
	/**
	 * Get assigned authorization provider.
	 *
	 * @param mixed $caller
	 * @return AuthorizationProvider
	 */
	public static function getAuthorizationProvider($caller)
	{
		return self::getObject($caller, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}

	/**
	 * Assign autorization provider to class.
	 *
	 * @param mixed $caller
	 * @param AuthorizationProvider $object 
	 */
	public static function setAuthorizationProvider($caller, AuthorizationProvider $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}
	
	/**
	 * Set default authorization provider.
	 *
	 * @param AuthorizationProvider $object 
	 */
	public static function setDefaultAuthorizationProvider(AuthorizationProvider $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_AUTHORIZATION_PROVIDER);
	}	
	
 /**
	 * Get assigned application configuration.
	 *
	 * @param mixed $caller
	 * @return ApplicationConfiguration
	 */
	public static function getApplicationConfiguration($caller)
	{
		return self::getObject($caller, self::INTERFACE_APPLICATION_CONFIGURATION);
	}

	/**
	 * Assign application configuration to namespace
	 *
	 * @param mixed $caller
	 * @param AuthorizationProvider $object 
	 */
	public static function setApplicationConfiguration($caller, ApplicationConfiguration $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_APPLICATION_CONFIGURATION);
	}
	
	/**
	 * Set application configuration.
	 *
	 * @param AuthorizationProvider $object 
	 */
	public static function setDefaultApplicationConfiguration(ApplicationConfiguration $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_APPLICATION_CONFIGURATION);
	}	

	/**
	 * Get Solarium client assigned for caller.
	 *
	 * @param mixed $caller
	 * @return Solarium_Client
	 */
	public static function getSolariumClient($caller)
	{
		return self::getObject($caller, self::INTERFACE_SOLARIUM_CLIENT);
	}

	/**
	 * Assign Solarium client to namespace.
	 * @param mixed $caller
	 * @param Solarium_Client $object 
	 */
	public static function setSolariumClient($caller, Solarium_Client $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_SOLARIUM_CLIENT);
	}
	
	/**
	 * Set default Solarium client.
	 * @param Solarium_Client $object 
	 */
	public static function setDefaultSolariumClient(Solarium_Client $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_SOLARIUM_CLIENT);
	}
	
	/**
	 * Get assigned event manager.
	 *
	 * @param mixed $caller
	 * @return EventManager
	 */
	public static function getEventManager($caller)
	{
		$eventManager = self::getObject($caller, self::INTERFACE_EVENT_MANAGER);
		
		if (is_null($eventManager)) {
			$eventManager = EventManager::getEmptyInstance();
		}
		
		return $eventManager;
	}

	/**
	 * Assign event manager to namespace.
	 *
	 * @param mixed $caller
	 * @param EventManager $object 
	 */
	public static function setEventManager($caller, EventManager $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_EVENT_MANAGER);
	}
	
	/**
	 * Set default event manager.
	 *
	 * @param EventManager $object 
	 */
	public static function setDefaultEventManager(EventManager $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_EVENT_MANAGER);
	}
	
	/**
	 * Get assigned ini configuration loader.
	 *
	 * @param mixed $caller
	 * @return IniConfigurationLoader
	 */
	public static function getIniConfigurationLoader($caller)
	{
		$iniConfigurationLoader = self::getObject($caller, self::INTERFACE_INI_CONFIGURATION);
		
		return $iniConfigurationLoader;
	}

	/**
	 * Assign ini configuration loader to namespace.
	 *
	 * @param mixed $caller
	 * @param IniConfigurationLoader $object 
	 */
	public static function setIniConfigurationLoader($caller, IniConfigurationLoader $object)
	{
		self::addBinding($caller, $object, self::INTERFACE_INI_CONFIGURATION);
	}
	
	/**
	 * Set default ini configuration loader.
	 *
	 * @param IniConfigurationLoader $object 
	 */
	public static function setDefaultIniConfigurationLoader(IniConfigurationLoader $object)
	{
		self::addBinding(self::DEFAULT_KEY, $object, self::INTERFACE_INI_CONFIGURATION);
	}
	
}
