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
 * Model Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Model {

	/**
	 * Class constructor
	 *
	 * @link	https://github.com/bcit-ci/CodeIgniter/issues/5332
	 * @return	void
	 */
	
	private $data_model = [];
	private $data_clean;
	private $form_error = [];
	private $form_use;
	private $data_bind;

	protected $forms = [];
	protected $form_lang = [];
	protected $main_table;

	public function __construct() {}

	public function data($data = -1){
		if($data == -1){
			return $this->data_model;
		}
		$this->data_model = $data;
		return $this;
	}

	public function data_clean(){
		return $this->data_clean;
	}

	public function form_error(){
		return $this->form_error;
	}

	public function use_form($id){
		$this->form_use = $id;
		return $this;
	}

	public function get_form(){
		return $this->forms[$this->form_use];
	}

	public function valid(){
		$id = $this->form_use;
		if(isset($this->forms[$id])){
			$this->form_validation->set_rules($this->data_model);
			foreach($this->forms[$id] as $key=>$value){
				if(count($this->form_lang) > 1){
					$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : [], $this->form_lang);
				} else {
					$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : []);
				}
			}
			if($this->form_validation->run() == true){
				foreach($this->get_form() as $key=>$value){
					$this->data_clean[$key] = $this->data_model[$key];
				}
				return true;
			} else {
				$this->form_error = $this->form_validation->error_array();
				return false;
			}
		} else {
			return true;
		}
	}

	public function db_get(){
		return $this->db->get($this->main_table);
	}

	public function db_insert($data){
		return $this->db->insert($this->main_table, $data);
	}

	public function db_update($data, $where = []){
		if($this->data_bind == null){
			if(isset($this->data_bind['id'])){
				$this->db->where('id', $this->data_bind['id']);
				return false;
			}
		} else {
			$this->db->where($where);
			$this->db->update($this->main_table, $data);
			return true;
		}
		
	}

	public function db_delete(){
		return $this->db->delete($this->main_table);
	}

	public function db_bind(){
		$data_bind = $this->db_get()->result_array();
		if(count($data_bind) > 0){
			$this->data_bind = $data_bind;
		} else {
			$this->data_bind = null;
		}
	}
	 
	

	/**
	 * __get magic
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string	$key
	 */
	public function __get($key)
	{
		// Debugging note:
		//	If you're here because you're getting an error message
		//	saying 'Undefined Property: system/core/Model.php', it's
		//	most likely a typo in your model code.
		return get_instance()->$key;
	}

}
