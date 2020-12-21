<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/general/controllers.html
 */

class Room{
	private $parent;
	private $list_room_replace;
	private $list_room_declare;
	private $list_room_will_replace;
	private $currentRoom;
	private $data;
	private $CI;


	public function __construct($will_replace, $ci, $data)
	{
		$this->list_room_will_replace = $will_replace;
		$this->CI = $ci;
		$this->data = $data;
	}

	public function extend($url){
		$this->parent = $url;
		foreach($this->list_room_will_replace as $key=>$value){
			$this->list_room_replace[$key] = $value;
		}
		$obj = new Room($this->list_room_replace, $this->CI, $this->data);
		$this->CI->load->view($url, ['room'=>$obj]);
	}

	public function declare($room){
		if(isset($this->list_room_will_replace[$room])){
			echo $this->list_room_will_replace[$room];
			unset($this->list_room_will_replace[$room]);
		};
	}

	public function open($room){
		if($this->currentRoom != NULL){
			show_error("You must close the room before open the room again");
		} else {
			$this->currentRoom = $room;
			if(!isset($this->list_room_replace[$room])){
				ob_start();
			}
		}
	}

	public function close(){
		if($this->currentRoom == NULL){
			show_error("You currently not open in any Room");
		} else {
			if(!isset($this->list_room_replace[$this->currentRoom])){
				$html = ob_get_clean();
				$this->list_room_replace[$this->currentRoom] = $html;
			}
			$this->currentRoom=NULL;
		}
	}
	
	public function include($view, $data=null){
		$room = new Room([], $this->CI, $data);
		$r = $this->CI->load->view($view, ['room'=>$room]);
	}

	public function data($key, $default = NULL){
		if($default != NULL){
			if(isset($this->data[$key])){
				if($this->data[$key] == NULL){
					return $default;
				}
			}
			if($this->data[$key] == NULL){
				return $default;
			}
		}
		return $this->data[$key];
	}

	public function alldata($params=NULL,$type = NULL){
		if($type==NULL){
			$type='only';
			if($params == NULL){
				$params = $this->data;
			}
		}
		if($params == NULL){
			$params = [];
		}
		$ret = []; 
		$dict_params = [];
		foreach($params as $key=>$value){
			$dict_params[$value] = 0;
		}
		if($type == 'except'){
			foreach($this->data as $key=>$value){
				if(!isset($dict_params[$key])){
					$ret[$key]=$value;
				}
			}
			return $ret;
		} else {
			foreach($this->data as $key=>$value){
				if(isset($dict_params[$key])){
					$ret[$key]  =$value;
				}
			}
			return $ret;
		}
	}
}

class RoomLoader{
	private $CI;
	private $shape;
	public function __construct($ci)
	{
		$this->CI = $ci;
	}

	public function load($view, $data=null){
		$room = new Room([], $this->CI, $data);
		$this->CI->load->view($view, ['room'=>$room]);
	}
	
}

class CI_Controller {

	/**
	 * Reference to the CI singleton
	 *
	 * @var	object
	 */
	private static $instance;

	/**
	 * CI_Loader
	 *
	 * @var	CI_Loader
	 */
	public $load;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */

	protected $middlewares = array();
	private $middlewares_obj = [];
	protected $options_middlewares = [];
	public $room;
	
	public function __construct()
	{
		self::$instance =& $this;

		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach (is_loaded() as $var => $class)
		{
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');
		$this->load->initialize();
		$this->room = new RoomLoader($this);
		
		$this->runMiddleware();
		log_message('info', 'Controller Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the CI singleton
	 *
	 * @static
	 * @return	object
	 */
	public static function &get_instance()
	{
		return self::$instance;
	}

	
    protected function runMiddleware(){
		$this->load->helper('inflector');
		$rtr =& get_router_instance();
		$data = $rtr->_get_middleware();
		foreach($this->middlewares as $mid){
			if(isset($data['params'][$mid])){
				unset($this->middlewares[$mid]);
			}
		}
		$this->middlewares = array_merge($this->middlewares, $data['middlewares']);
        foreach($this->middlewares as $middleware){
			$is_filter = true;
			$options=[];
            if(isset($this->options_middlewares[$middleware])){
                $options = $this->options_middlewares[$middleware];
                $type = isset($options['type_method']) ? $options['type_method'] : 'only';
				$methods = $options['list_method'];
                if ($type == 'except') {
					$is_filter = !(in_array($this->router->method, $methods));
                } else if ($type == 'only') {
					$is_filter = in_array($this->router->method, $methods);
                }
			}
            $file = ucfirst(camelize($middleware));
            if ($is_filter == true) {
                if (file_exists(APPPATH . 'middlewares/' . $file . '.php')) {
					require APPPATH . 'middlewares/' . $file . '.php';
					$param = isset($options['params']) ? $options['params'] : [];
					if(isset($data['params'])){
						if(isset($data['params'][$middleware])){
							$param = array_merge($param, $data['params'][$middleware]);
						}
					}
					$object = new $file($this, $param);
					$cek = $object->run();
					$ea = $object->post_run($cek);
					if(isset($options['break']) && $cek && $options['break'] == true) break;
					$this->middlewares_obj[$middleware] = $object;
                } else {
                    if (ENVIRONMENT == 'development') {
                        show_error('File Middleware not exist: ' . $file . '.php');
                    } else {
                        show_error('Internal server error.');
                    }
                }
            }
        }
	}

}
