<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\adapter;

use \RuntimeException;

/**
 * A minimal adapter to interface with native PHP sessions.
 *
 * This adapter provides basic support for `write`, `read` and `delete`
 * session handling, as well as allowing these three methods to be filtered as
 * per the Lithium filtering system.
 *
 */
class Php extends \lithium\core\Object {

	/**
	 * Default ini settings for this session adapter.
	 *
	 * @var array Keys are session ini settings, but without the `session.` namespace.
	 */
	protected $_defaults = array(
		'session.cookie_lifetime' => '86400',
	);

	/**
	 * Class constructor.
	 *
	 * Takes care of setting appropriate configurations for this object.
	 *
	 * @param array $config Unified constructor configuration parameters. You can set
	 *        the `session.*` PHP ini settings here as key/value pairs.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		parent::__construct($config + $this->_defaults);
	}

	/**
	 * Initialization of the session.
	 *
	 * @todo Split up into an _initialize() and a _startup().
	 * @return void
	 */
	protected function _init() {
		$config = $this->_config;
		unset($config['adapter'], $config['strategies'], $config['filters'], $config['init']);

		if (!isset($config['session.name'])) {
			$config['session.name'] = basename(LITHIUM_APP_PATH);
		}
		foreach ($config as $key => $value) {
			if (strpos($key, 'session.') === false) {
				continue;
			}
			if (ini_set($key, $value) === false) {
				throw new RuntimeException("Could not initialize the session.");
			}
		}
	}

	/**
	 * Starts the session.
	 *
	 * @return boolean True if session successfully started (or has already been started),
	 *         false otherwise.
	 */
	protected static function _startup() {
		if (session_id()) {
			return true;
		}
		if (!isset($_SESSION)) {
			session_cache_limiter('nocache');
		}
		return session_start();
	}

	/**
	 * Obtain the status of the session.
	 *
	 * @return boolean True if $_SESSION is accessible and if a '_timestamp' key
	 *         has been set, false otherwise.
	 */
	public static function isStarted() {
		return (boolean) session_id();
	}

	/**
	 * Obtain the session id.
	 *
	 * @return mixed Session id, or null if the session has not been started.
	 */
	public static function key() {
		return session_id() ?: null;
	}

	/**
	 * Checks if a value has been set in the session.
	 *
	 * @param string $key Key of the entry to be checked.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True if the key exists, false otherwise.
	 */
	public static function check($key, array $options = array()) {
		if (!static::isStarted() && !static::_startup()) {
			throw new RuntimeException("Could not start session.");
		}
		return function($self, $params, $chain) {
			return (isset($_SESSION[$params['key']]));
		};
	}

	/**
	 * Read a value from the session.
	 *
	 * @param null|string $key Key of the entry to be read. If no key is passed, all
	 *        current session data is returned.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return mixed Data in the session if successful, false otherwise.
	 */
	public static function read($key = null, array $options = array()) {
		if (!static::isStarted() && !static::_startup()) {
			throw new RuntimeException("Could not start session.");
		}
		return function($self, $params, $chain) {
			$key = $params['key'];

			if (!$key) {
				return $_SESSION;
			}
			return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
		};
	}

	/**
	 * Write a value to the session.
	 *
	 * @param string $key Key of the item to be stored.
	 * @param mixed $value The value to be stored.
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful write, false otherwise
	 */
	public static function write($key, $value, array $options = array()) {
		if (!static::isStarted() && !static::_startup()) {
			throw new RuntimeException("Could not start session.");
		}
		return function($self, $params, $chain) {
			$_SESSION[$params['key']] = $params['value'];
		};
	}

	/**
	 * Delete value from the session
	 *
	 * @param string $key The key to be deleted
	 * @param array $options Options array. Not used for this adapter method.
	 * @return boolean True on successful delete, false otherwise
	 */
	public static function delete($key, array $options = array()) {
		if (!static::isStarted() && !static::_startup()) {
			throw new RuntimeException("Could not start session.");
		}
		return function($self, $params, $chain) {
			$key = $params['key'];

			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
				return true;
			}
			return false;
		};
	}

	/**
	 * Determines if PHP sessions are enabled.
	 *
	 * @return boolean True if enabled (that is, if session_id() returns a value), false otherwise.
	 */
	public static function enabled() {
		return (boolean) session_id();
	}
}

?>