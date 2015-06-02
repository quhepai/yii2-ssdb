<?php
/**
 * SessionHandler - yii2-ssdb
 *
 * @author Jack.wu <xiaowu365@gmail.com>
 * @create_time 2015-06-02 14:29
 */

namespace ijackwu\ssdb;

use yii\base\Component;

class SessionHandler extends Component implements \SessionHandlerInterface {

	/**
	 *
	 * @var string
	 */
	public $_prefix = 'sess_';

	/**
	 *
	 * @var int
	 */
	private $_ttl;

	/**
	 * 
	 * @var Client
	 */
	protected $_client;

	/**
	 * 
	 * @var array
	 */
	protected $_cache = array();

	public function init() {
		parent::init();
		$this->_ttl = ini_get('session.gc_maxlifetime');
	}

	/**
	 * 
	 * @return boolean
	 */
	public function close() {
		return true;
	}

	/**
	 * 
	 * @param string $session_id
	 * @return boolean
	 */
	public function destroy($session_id) {
		$this->_client->del($this->_prefix . $session_id);

		return true;
	}

	/**
	 * 
	 * @param int $maxlifetime
	 * @return boolean
	 */
	public function gc($maxlifetime) {
		return true;
	}

	/**
	 * @param string $save_path
	 * @param string $name
	 * @return bool
	 * @throws Exception
	 */
	public function open($save_path, $name) {
		$components = parse_url($save_path);
		
		if ($components === false
			|| !isset($components['scheme'], $components['host'], $components['port'])
			|| strtolower($components['scheme']) !== 'tcp')
			throw new Exception('Invalid session.save_path: ' . $save_path);
		
		$this->_client = new Client($components['host'], $components['port']);
		
		if (isset($components['query'])){
			parse_str($components['query'], $query);
			
			if (isset($query['auth']))
				$this->_client->auth($query['auth']);
		}
		
		return true;
	}

	/**
	 * 
	 * @param string $session_id
	 * @return string
	 */
	public function read($session_id) {
		if (isset($this->_cache[$session_id]))
			return $this->_cache[$session_id];

		$session_data = $this->_client->get($this->_prefix . $session_id)->data;

		return $this->_cache[$session_id] = ($session_data === null ? '' : $session_data);
	}

	/**
	 * 
	 * @param string $session_id
	 * @param string $session_data
	 * @return boolean
	 */
	public function write($session_id, $session_data) {
		if (isset($this->_cache[$session_id]) && $this->_cache[$session_id] === $session_data) {
			$this->_client->expire($this->_prefix . $session_id, $this->_ttl);
		}
		else {
			$this->_cache[$session_id] = $session_data;
			$this->_client->setx($this->_prefix . $session_id, $session_data, $this->_ttl);
		}
		
		return true;
	}
}
