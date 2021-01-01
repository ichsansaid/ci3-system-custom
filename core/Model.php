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

function customError($errno, $errstr) {
	echo "<b>Error:</b> [$errno] $errstr";
	die();
}

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
	protected $__table;

	protected $forms = [];
	protected $form_lang = [];
	protected $main_table;
	protected $import_models = [];
	protected $selected_form;

	public function __construct() {
		set_error_handler("customError");
		foreach($this->import_models as $key=>$value){
			if(is_string($key)){
				if(!isset(get_instance()->$value)){
					get_instance()->load->model($key, $value);
				}
			} else {
				if(!isset(get_instance()->$value)){
					get_instance()->load->model($value);
				}
			}
		}
		$this->pre_load();
		$this->form = $this->form();
	}

	public function table($form){
		return $this->form[$id]['__table'];
	}

	protected function pre_load(){
		return;
	}

	protected function form(){
		return null;
	}

	public function get_form($id=null){
		if($id !== null){
			if(count($this->form) == 0){
				$this->form = $this->form();
			}
			if(!isset($this->form[$id])){
				show_error("Form ".$id." not exist");
			}
			if(!isset($this->form[$id]['__table']))
				$this->form[$id]['__table'] = $this->__table;
			return $this->form[$id];
		} else {
			return $this->form;
		}
		
	}

	public function get_data_model(){
		return $this->data_model;
	}

	public function data($data = -1){
		if($data == -1){
			return $this->data_model;
		}
		$this->data_model = $data;
		return $this;
	}

	public function data_clean($form = null){
		if($form == '__all__'){
			$return = [];
			foreach($this->data_clean as $key=>$value){
				$return = array_merge($return, $value);
			}
			return $return;
		}
		if($form == null){
			if(count($this->form_use) > 0){
				return $this->data_clean[$this->form_use[count($this->form_use) - 1]];
			} else {
				return [];
			}
		}
		if(isset($this->data_clean[$form])){
			return $this->data_clean[$form];
		}
		return [];
	}

	public function form_error(){
		return $this->form_error;
	}

	public function use_form($id){
		if(!is_array($id)){
			$ganti = [$id];
		} else {
			$ganti = $id;
		}
		foreach($ganti as $val){
			if(!isset($this->form[$val])){
				$sss = false;
				foreach($this->import_models as $key=>$value){
					if(isset(get_instance()->$value)){
						if(isset(get_instance()->$value->get_form()[$val])){
							$this->form[$val]=get_instance()->$value->get_form()[$val];
							$sss=true;
							break;
						}
					}
				}
				if($sss === false) show_error("Form ".$id." not exist");
			}
		}
		$this->form_use = $ganti;
		return $this;
	}

	public function select_form($id){
		$this->selected_form = $id;
		return $this;
	}

	public function valid(){
		$ids = $this->form_use;
		if(!is_array($ids)){
			$ids = [$ids];
		}
		foreach($ids as $id){
			if(isset($this->form[$id])){
				$this->form_validation->set_data($this->data_model);
				foreach($this->form[$id] as $key=>$value){
					if($key != '__table'){
						foreach($value[1] as $key2=>$value2){
							if($key2 === '__create' || $key2 === '__update' || $key2 === '__filter'){
								unset($value[1][$key2]);
							}
						}
						if(count($this->form_lang) > 1){
							$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : [], $this->form_lang);
						} else {
							$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : []);
						}
					}
				}
			}
		}
		if($this->form_validation->run() == true){
			foreach($this->form as $key=>$value){
				if($key != '__table'){
					if(is_array($value)){
						foreach($value as $key2=>$value2){
							if(isset($this->data_model[$key2])){
								$this->data_clean[$key][$key2] = $this->data_model[$key2];
							}
						}
					}
				}
			}
		} else {
			$this->form_error = $this->form_validation->error_array();
		}
		return count($this->form_error) <= 0;
	}

	public function get_latest_form(){
		if(isset($this->form[$this->form_use[count($this->form_use) - 1]])) {
			return $this->form_use[count($this->form_use) - 1];
		}
	}

	public function get_table_form($form){
		$table = "";
		if($form == null){
			$form = $this->get_latest_form();
		}
		$form = $this->form[$form];
		if(isset($form['__table'])){
			$table = $form['__table'];
		} else if($this->__table !== null){
			$table = $this->__table;
		}
		if($table == ''){
			show_error('Kamu harus menambah __table pada Form / Model');
		}
		return $table;
	}

	public function __toString()
    {
        return $this->__table;
	}

	public function datatable($form, $type='get')
	{
		if($type == 'get'){
			$draw = intval($this->input->get("draw"));
			$start = intval($this->input->get("start"));
			$length = intval($this->input->get("length"));
			$order = $this->input->get("order");
			$search = $this->input->get("search");
		} else {
			$draw = intval($this->input->post("draw"));
			$start = intval($this->input->post("start"));
			$length = intval($this->input->post("length"));
			$order = $this->input->post("order");
			$search = $this->input->post("search");
		}
		
		$search = $search['value'];
		$col = 0;
		$dir = "";
		$fields = [];
		foreach($this->get_form($form) as $key=>$value){
			if($key !== '__table'){
				array_push($fields, $key);
			}
		}
		$sel = implode($fields, ",");
		$this->db->select($sel)->from($this->__table);
		$qr = $this->db->_compile_select(); 
		if (!empty($order)) {
			foreach ($order as $o) {
				$col = $o['column'];
				$dir = $o['dir'];
			}
		}
		if (!isset($fields[$col])) {
			$order = null;
		} else {
			$order = $fields[$col];
		}
		if ($order != null) {
			$this->db->order_by($order, $dir);
		}
		if (!empty($search)) {
			$x = 0;
			foreach ($fields as $sterm) {
				if ($x == 0) {
					$this->db->like($sterm, $search);
				} else {
					$this->db->or_like($sterm, $search);
				}
				$x++;
			}
		}
		$this->db->limit($length, $start);
		$results = $this->db->query($this->db->_compile_select());
		$data = array();
		foreach ($results->result_array() as $rows) {
			$arr = [];
			foreach($rows as $value){
				$arr[] = $value;
			}
			$data[] = $arr;
		}
		$total_employees = $this->db->query($qr)->num_rows();
		$output = array(
			"draw" => $draw,
			"recordsTotal" => $total_employees,
			"recordsFiltered" => $total_employees,
			"data" => $data
		);
		return $output;
	}


	public function create($form = null, $additional_data=[]){
		if($form == null){
			if($this->selected_form != null){
				$form = $this->selected_form;
			} else {
				$form = $this->get_latest_form();
			}
		}
		$table = $this->get_table_form($form);
		foreach($this->form[$form] as $key2=>$value2){
			if(is_array($value2)){
				foreach($value2 as $key3=>$value3){
					if(is_array($value3)){
						foreach($value3 as $key4=>$value4){
							if($key4 === '__filter' || $key4 === '__create'){
								$ret = $value4($key2, $this->data_clean, $this);
								if(!is_array($ret)){
									$ret = [$ret];
								}
								if(count($ret) > 1){
									$this->data_clean[$form][$ret[1]] = $ret[0];
									unset($this->data_clean[$form][$key2]);
								} else {
									$this->data_clean[$form][$key2] = $ret[0];
								}
							}
						}
					}
				}
			}
		}
		$this->db_insert(array_merge($this->data_clean($form), $additional_data), $table);
		$id = $this->db->insert_id();
		$this->bind($id, $form);
	}

	public function delete($form = null){
		if($form == null){
			if($this->selected_form != null){
				$form = $this->selected_form;
			}
		}
		$table = $this->get_table_form($form);
		$this->db_delete($table);
	}

	public function update($form = null){
		if($form == null){
			if($this->selected_form != null){
				$form = $this->selected_form;
			}
		}
		$table = $this->get_table_form($form);
		if($form == null){
			$form = $this->form_use[count($this->form_use) - 1];
		}
		foreach($this->form[$form] as $key2=>$value2){
			if(is_array($value2)){
				foreach($value2 as $key3=>$value3){
					if(is_array($value3)){
						foreach($value3 as $key4=>$value4){
							if($key4 === '__filter' || $key4 === '__update'){
								$ret = $value4($key2, $this->data_clean, $this);
								if(!is_array($ret)){
									$ret = [$ret];
								}	
								if(count($ret) > 1){
									$this->data_clean[$form][$ret[1]] = $ret[0];
									unset($this->data_clean[$form][$key2]);
								} else {
									$this->data_clean[$form][$key2] = $ret[0];
								}
							}
						}
					}
				}
			}
		}
		$this->db_update($this->data_clean($form), $table);
		return true;
	}

	public function get($form){
		$table = $this->get_table_form($form);
		return $this->db_get($table);
	}
	

	public function db_get($table){
		return $this->db->get($table);
	}

	public function db_insert($data, $table){
		$this->db->insert($table, $data);
		
	}

	public function db_update($data, $table){
		if(!$this->data_bind == null){
			if(isset($this->data_bind[$table])){
				if(isset($this->data_bind[$table]['id'])){
					$this->db->where('id', $this->data_bind[$table]['id']);
				} else {
					show_error("ID Data Binding Error");
				}
			} else {
				show_error("Data binding not exist");
			}
		} else {
			show_error("Data binding empty");
		}
		$this->db->update($table, $data);
		return true;
	}

	public function db_delete($table){
		if(!$this->data_bind == null){
			if(isset($this->data_bind[$table])){
				if(isset($this->data_bind[$table]['id'])){
					$this->db->where('id', $this->data_bind[$table]['id']);
				} else {
					show_error("ID Data Binding Error");
				}
			} else {
				show_error("Data binding not exist");
			}
		} else {
			show_error("Data binding failed");
		}
		return $this->db->delete($table);
	}

	public function data_bind($form){
		$table = $this->get_table_form($form);
		return $this->data_bind[$table];
	}

	public function bind($pk = null, $form = null){
		if($pk !== null){
			if(!is_array($pk)){
				$this->db->where($pk);
			} else {
				$this->db->where('id', $pk);
			}
		}
		if($form == null){
			if($this->selected_form != null){
				$form = $this->selected_form;
			}
		}
		$table = $this->get_table_form($form);
		$data_bind = $this->db_get($table)->result_array();
		if(count($data_bind) > 0){
			$this->data_bind[$table] = $data_bind[0];
		} else {
			unset($this->data_bind[$table]);
		}
		return $this;
	}

	public function is_null(){
		return $this->data_bind == null;
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
