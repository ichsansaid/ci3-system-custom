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
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/config.html
 */

function customError($errno, $errstr)
{
	echo "<b>Error:</b> [$errno] $errstr";
	die();
}

class CI_Model
{

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
	private $file_config = [];
	private $validation_model = [];
	protected $__table;

	protected $forms = [];
	protected $form_lang = [];
	protected $main_table;
	protected $import_models = [];
	protected $selected_form;
	protected $__primary_key;

	public function __construct($name)
	{
		$CI = &get_instance();
		$CI->$name = $this;
		// echo "\n+".$this."\n";
		foreach ($this->import_models as $key => $value) {
			if (is_string($key)) {
				if (!isset($CI->$value)) {
					$CI->load->model($key, $value);
				}
			} else {
				if (!isset($CI->$value)) {
					$CI->load->model($value);
				}
			}
		}
		$this->pre_load();
		$this->form = $this->form();
		foreach ($this->form as $id => $value) {
			if (!isset($this->form[$id]['__table'])) {
				$this->form[$id]['__table'] = $this->__table;
			}
		}
	}

	public function table($form = null)
	{
		if ($form == null) return $this->__table;
		return $this->form[$form]['__table'];
	}

	public function list()
	{
		return $this->db_get($this->__table)->result_array();
	}


	protected function pre_load()
	{
		return;
	}

	protected function form()
	{
		return null;
	}

	public function get_form($id = null)
	{
		if ($id !== null) {
			if (count($this->form) == 0) {
				$this->form = $this->form();
			}
			if (!isset($this->form[$id])) {
				show_error("Form " . $id . " not exist");
			}
			if (!isset($this->form[$id]['__table']))
				$this->form[$id]['__table'] = $this->__table;
			return $this->form[$id];
		} else {
			return $this->form;
		}
	}

	public function get_data_model($key = null)
	{
		if ($key == null) return $this->data_model;
		return isset($this->data_model[$key]) ? $this->data_model[$key] : null;
	}

	public function data($data = -1)
	{
		if ($data == -1) {
			return $this->data_model;
		}
		$this->data_model = $data;
		$this->form = $this->form();
		return $this;
	}

	public function data_clean($form = null)
	{
		if ($form == '__all__') {
			$return = [];
			foreach ($this->data_clean as $key => $value) {
				$return = array_merge($return, $value);
			}
			return $return;
		}
		if ($form == null) {
			if (count($this->form_use) > 0) {
				return $this->data_clean[$this->form_use[count($this->form_use) - 1]];
			} else {
				return [];
			}
		}
		if (isset($this->data_clean[$form])) {
			return $this->data_clean[$form];
		}
		return [];
	}

	public function form_error()
	{
		return $this->form_error;
	}

	public function use_form($id)
	{
		if (!is_array($id)) {
			$ganti = [$id];
		} else {
			$ganti = $id;
		}
		foreach ($ganti as $val) {
			if (!isset($this->form[$val])) {
				$sss = false;
				foreach ($this->import_models as $key => $value) {
					if (isset(get_instance()->$value)) {
						if (isset(get_instance()->$value->get_form()[$val])) {
							$this->form[$val] = get_instance()->$value->get_form()[$val];
							$sss = true;
							break;
						}
					}
				}
				if ($sss === false) show_error("Form " . $val . " not exist");
			}
		}
		$this->form_use = $ganti;
		return $this;
	}

	public function select_form($id)
	{
		$this->selected_form = $id;
		return $this;
	}

	public function force_valid()
	{
		$ids = $this->form_use;
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		$err_file = [];
		foreach ($ids as $id) {
			if (isset($this->form[$id])) {
				foreach ($this->get_form($id) as $key => $value) {
					if ($key != '__table') {
						foreach ($value[1] as $key2 => $value2) {
							if ($key2 === '__create' || $key2 === '__update' || $key2 === '__filter') {
								unset($value[1][$key2]);
							} else if ($key2 === '__file') {
								$this->file_config[$key] = $value2;
								foreach ($value2 as $keyconf => $valconf) {
									if ($valconf === null) {
										unset($value2[$keyconf]);
									}
								}
								$this->load->library('upload', $value2);
								if (isset($_FILES[$key])) {
									$file_name = $_FILES[$key]['name'];
									$file_name = explode(".", $file_name);
									$file_name[count($file_name) - 1] = strtolower($file_name[count($file_name) - 1]);
									$_FILES[$key]['name'] = implode(".", $file_name);
									$path = isset($value2['upload_path']) ? $value2['upload_path'] : '';
									$path = substr($path, 1, strlen($path) - 1);
									$this->data_model[$key] = $path . $this->upload->data()['file_name'];
								}
								unset($value[1][$key2]);
							} else {
								$len = strlen('!');
								if ((substr($key2, 0, $len) === '!')) {
									unset($value[1]["!" . $key2]);
								}
							}
						}
					}
				}
			}
		}
		foreach ($this->form as $key => $value) {
			if ($key != '__table') {
				if (is_array($value)) {
					foreach ($value as $key2 => $value2) {
						if (isset($this->data_model[$key2])) {
							$this->data_clean[$key][$key2] = $this->data_model[$key2];
						}
					}
				}
			}
		}
		return $this;
	}

	public function valid()
	{
		$ids = $this->form_use;
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		$err_file = [];
		foreach ($ids as $id) {
			if (isset($this->form[$id])) {
				foreach ($this->get_form($id) as $key => $value) {
					if ($key != '__table') {
						$form_lang_tambahan = [];
						foreach ($value[1] as $key2 => $value2) {
							if ($key2 === '__create' || $key2 === '__update' || $key2 === '__filter') {
								unset($value[1][$key2]);
							} else if ($key2 === '__file') {
								$this->file_config[$key] = $value2;
								$this->load->library('upload', $value2);
								if (isset($_FILES[$key])) {
									$file_name = $_FILES[$key]['name'];
									$file_name = explode(".", $file_name);
									$file_name[count($file_name) - 1] = strtolower($file_name[count($file_name) - 1]);
									$_FILES[$key]['name'] = implode(".", $file_name);
									if (!$this->upload->valid($key)) {
										$err_file[$key] = $this->upload->display_errors('', '');
									} else {
										$path = isset($value2['upload_path']) ? $value2['upload_path'] : '';
										$path = substr($path, 1, strlen($path) - 1);
										$this->data_model[$key] = $path . $this->upload->data()['file_name'];
									}
								} else {
									if (!$this->upload->valid($key)) {
										$err_file[$key] = $this->upload->display_errors('', '');
									}
								}
								unset($value[1][$key2]);
							} else {
								$first_word = substr($key2, 0, 1);
								if ($first_word === '!') {
									$key2 = str_replace("!", "", $key2);
									if (method_exists($this->validation, $key2)) {
										$this->validation->setParams($value2)->setValidation($key2);
										if (isset($value2['pesan'])) {
											$this->form_lang[$key2] = $value2['pesan'];
										}
										$this->validation_model[$key][$key2] = clone $this->validation;
										$this->validation->reset();
										array_push($value[1], [$key2, function ($asd) use ($key, $key2) {
											return $this->validation_model[$key][$key2]->run($key, $asd);
										}]);
									} else {
										show_error("Validation $key2 not valid");
									}
									unset($value[1]["!" . $key2]);
								} else if ($key2 === '__errors') {
									$form_lang_tambahan = array_merge($form_lang_tambahan, $value[1][$key2]);
									unset($value[1][$key2]);
								}
							}
						}
						if (isset($this->data_bind[$this->form[$id]['__table']][$key])) {
							if (isset($this->data_model[$key])) {
								if ($this->data_bind[$this->form[$id]['__table']][$key] === $this->data_model[$key]) {
									$this->data_clean[$id][$key] = $this->data_model[$key];
									continue;
								}
							}
						}
						$this->form_lang = array_merge($this->form_lang, $form_lang_tambahan);
						if (count($this->form_lang) > 1) {
							$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : [], $this->form_lang);
						} else {
							$this->form_validation->set_rules($key, $value[0], isset($value[1]) ? $value[1] : []);
						}
					}
				}
			}
		}
		$this->form_validation->set_data($this->data_model);
		if ($this->form_validation->run() == true) {
			foreach ($this->form as $key => $value) {
				if ($key != '__table') {
					if (is_array($value)) {
						foreach ($value as $key2 => $value2) {
							if (isset($this->data_model[$key2])) {
								$this->data_clean[$key][$key2] = $this->data_model[$key2];
							}
						}
					}
				}
			}
			$this->form_error = $err_file;
		} else {
			$this->form_error = array_merge($this->form_validation->error_array(), $err_file);
		}
		return count($this->form_error) <= 0;
	}

	public function get_latest_form()
	{
		if (isset($this->form[$this->form_use[count($this->form_use) - 1]])) {
			return $this->form_use[count($this->form_use) - 1];
		}
	}

	public function get_object()
	{
		return $this;
	}

	public function get_table_form($form)
	{
		$table = "";
		if ($form == null) {
			$form = $this->get_latest_form();
		}
		$form = $this->form[$form];
		if (isset($form['__table'])) {
			$table = $form['__table'];
		} else if ($this->__table !== null) {
			$table = $this->__table;
		}
		if ($table == '') {
			show_error('Kamu harus menambah __table pada Form / Model');
		}
		return $table;
	}

	public function __toString()
	{
		return $this->__table;
	}

	public function datatable_query($field, $type = 'get')
	{
		$draw = "";
		$start = "";
		$length = "";
		$order = "";
		$search = "";
		$draw = intval($this->input->$type("draw"));
		$start = intval($this->input->$type("start"));
		$length = intval($this->input->$type("length"));
		$order = $this->input->$type("order");
		$search = $this->input->$type("search");
		$columns = $this->input->$type("columns");
		$search = $search['value'];
		$col = 0;
		$dir = "";
		$fields;
		$field_select = [];
		foreach ($field as $key => $value) {
			if (is_string($key)) {
				$fields[] = $value;
				$field_select[] = "$key as '$value'";
			} else {
				$fields[] = $value;
				$field_select[] = "$value as '$value'";
			}
		}
		$sel = implode(",", $field_select);
		$this->db->select($sel);
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
		$x = 0;
		if (!empty($search)) {
			foreach ($fields as $sterm) {
				if ($x == 0) {
					$this->db->like($sterm, $search);
				} else {
					$this->db->or_like($sterm, $search);
				}
				$x++;
			}
		}

		foreach ($columns as $value) {
			if ($value['searchable'] == true) {
				if ($value['search']['value'] !== '') {
					if ($x == 0) {
						$this->db->like($value['data'], $value['search']['value']);
					} else {
						$this->db->or_like($value['data'], $value['search']['value']);
					}
				}
			}
		}
		$this->db->limit($length, $start);
		$results = $this->db->query($this->db->_compile_select());
		$data = $results->result_array();
		foreach ($data as $i => $arr) {
			foreach ($arr as $key => $value) {
				$split = explode('.', $key);
				if (count($split) > 1) {
					$data[$i][$split[0]][$split[1]] = $value;
					unset($data[$i][$key]);
				}
			}
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

	public function datatable($form = null, $field = null, $type = 'get')
	{
		$draw = "";
		$start = "";
		$length = "";
		$order = "";
		$search = "";
		$draw = intval($this->input->$type("draw"));
		$start = intval($this->input->$type("start"));
		$length = intval($this->input->$type("length"));
		$order = $this->input->$type("order");
		$search = $this->input->$type("search");
		$search = $search['value'];
		$col = 0;
		$dir = "";
		$fields = [];
		if ($field == null) {
			if ($form !== null) {
				foreach ($this->get_form($form) as $key => $value) {
					if ($key !== '__table') {
						array_push($fields, $key);
					}
				}
			}
		} else {
			$fields = $field;
		}
		$sel = implode(",", $fields);
		$table = $form !== null ?  $this->get_form($form)['__table'] : $this->__table;
		$this->db->select($sel)->from($table);
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
		$data = $results->result_array();
		$total_employees = $this->db->query($qr)->num_rows();
		$output = array(
			"draw" => $draw,
			"recordsTotal" => $total_employees,
			"recordsFiltered" => $total_employees,
			"data" => $data
		);
		return $output;
	}


	public function create($form = null, $additional_data = [])
	{
		$form_data = [];
		if ($form == null) {
			if ($this->form_use != null) {
				$form = $this->form_use;
				if (is_array($form)) {
					$before = null;
					foreach ($form as $key => $data) {
						foreach ($this->form[$data] as $key_data => $value) {
							if ($key_data === '__table') {
								if ($before == null) {
									$before = $value;
								} else if ($before !== $value) {
									show_error("You only can select multi form with same table");
									die();
								}
							}
							$form_data[$key_data] = $value;
						}
					}
				}
			} else {
				$form = $this->get_latest_form();
			}
		}
		$form_data = count($form_data) > 0 ? $form_data : $this->form[$form];
		$table;
		if (is_array($form)) {
			$table = $this->get_table_form($form[0]);
		} else {
			$form = [$form];
			$table = $this->get_table_form($form[0]);
		}
		foreach ($form as $key => $theform) {
			foreach ($this->form[$theform] as $key2 => $value2) {
				if (is_array($value2)) {
					foreach ($value2 as $key3 => $value3) {
						if (is_array($value3)) {
							foreach ($value3 as $key4 => $value4) {
								if ($key4 === '__filter' || $key4 === '__create') {

									$ret = $value4($key2, $this->data_clean, $this);
									if (!is_array($ret)) {
										$ret = [$ret];
									}
									if (count($ret) > 1) {
										$this->data_clean[$theform][$ret[1]] = $ret[0];
										unset($this->data_clean[$theform][$key2]);
									} else {
										$this->data_clean[$theform][$key2] = $ret[0];
									}
								} else if ($key4 === '__file') {
									$this->load->library('upload', $this->file_config[$key2]);
									$path = isset($this->file_config[$key2]['upload_path']) ? $this->file_config[$key2]['upload_path'] : '';
									$path = substr($path, 2, strlen($path) - 1);
									$this->upload->do_upload($key2);
									$this->data_clean[$theform][$key2] = $path . $this->upload->data()['file_name'];
								}
							}
						}
					}
				}
			}
		}
		$arr_data = [];
		foreach ($form as $key => $theform) {
			$arr_data = array_merge($arr_data, $this->data_clean($theform));
		}
		$arr_data = array_merge($arr_data, $additional_data);
		$this->db_insert($arr_data, $table);
		$id = $this->db->insert_id();
		$this->bind($id, $form[0]);
	}

	public function delete($form = null)
	{
		if ($form == null) {
			if ($this->selected_form != null) {
				$form = $this->selected_form;
			}
		}
		$table = $this->get_table_form($form);
		$this->load->helper("file");
		if (count($this->data_bind) > 0) {
			if (isset($this->data_bind[$table])) {
				if (isset($this->data_bind[$table]['id'])) {
					$this->db->where('id', $this->data_bind[$table]['id']);
				} else {
					show_error("ID Data Binding Error");
				}
			} else {
				show_error("Data binding not exist");
			}
		} else {
			show_error("Page Not found", 404);
		}
		foreach ($this->form[$form] as $key2 => $value2) {
			if (is_array($value2)) {
				foreach ($value2 as $key3 => $value3) {
					if (is_array($value3)) {
						foreach ($value3 as $key4 => $value4) {
							if ($key4 === '__file') {
								if (isset($value4['delete_file'])) {
									if ($value4['delete_file'] === true) {
										@unlink(("./" . $this->data_bind[$table][$key2]));
									}
								}
							}
						}
					}
				}
			}
		}
		$this->db_delete($table);
	}

	public function update($form = null)
	{
		if ($form == null) {
			if ($this->selected_form != null) {
				$form = $this->selected_form;
			}
		}
		$table = $this->get_table_form($form);
		if ($form == null) {
			$form = $this->form_use[count($this->form_use) - 1];
		}
		if (count($this->data_bind) > 0) {
			if (isset($this->data_bind[$table])) {
				if (isset($this->data_bind[$table]['id'])) {
					$this->db->where('id', $this->data_bind[$table]['id']);
				} else {
					show_error("ID Data Binding Error");
				}
			} else {
				show_error("Data binding not exist");
			}
		} else {
			show_error("Page Not found", 404);
		}
		foreach ($this->form[$form] as $key2 => $value2) {
			if (is_array($value2)) {
				foreach ($value2 as $key3 => $value3) {
					if (is_array($value3)) {
						foreach ($value3 as $key4 => $value4) {
							if ($key4 === '__filter' || $key4 === '__update') {
								$ret = $value4($key2, $this->data_clean, $this);
								if (!is_array($ret)) {
									$ret = [$ret];
								}
								if (count($ret) > 1) {
									$this->data_clean[$form][$ret[1]] = $ret[0];
									unset($this->data_clean[$form][$key2]);
								} else {
									$this->data_clean[$form][$key2] = $ret[0];
								}
							} else if ($key4 === '__file') {
								if (isset($this->file_config[$key2]['delete_file'])) {
									if ($this->file_config[$key2]['delete_file'] === true) {
										@unlink("./" . $this->data_bind[$table][$key2]);
									}
								}
								$this->load->library('upload', $this->file_config[$key2]);
								$path = isset($this->file_config[$key2]['upload_path']) ? $this->file_config[$key2]['upload_path'] : '';
								$path = substr($path, 2, strlen($path) - 1);
								$this->upload->do_upload($key2);
								$this->data_clean[$form][$key2] = $path . $this->upload->data()['file_name'];
							}
						}
					}
				}
			}
		}
		$this->db_update($this->data_clean($form), $table);
		return true;
	}

	public function get($form)
	{
		$table = $this->get_table_form($form);
		return $this->db_get($table);
	}


	public function db_get($table)
	{
		return $this->db->get($table);
	}

	public function db_insert($data, $table)
	{
		$this->db->insert($table, $data);
	}

	public function db_update($data, $table)
	{

		$this->db->update($table, $data);
		return true;
	}

	public function db_delete($table)
	{
		if (!$this->data_bind == null) {
			if (isset($this->data_bind[$table])) {
				if (isset($this->data_bind[$table]['id'])) {
					$this->db->where('id', $this->data_bind[$table]['id']);
				} else {
					show_error("ID Data Binding Error");
				}
			} else {
				show_error("Data binding not exist");
			}
		} else {
			show_error("Page not found", 404);
		}
		return $this->db->delete($table);
	}

	public function data_bind($form)
	{
		$table = $this->get_table_form($form);
		if (isset($this->data_bind[$table])) {
			return $this->data_bind[$table];
		}
		return [];
	}

	public function bind($pk = null, $form = null)
	{
		if ($pk !== null) {
			if (is_array($pk)) {
				$this->db->where($pk);
			} else {
				$this->db->where('id', $pk);
			}
		}
		if ($form == null) {
			if ($this->selected_form != null) {
				$form = $this->selected_form;
			}
		}
		if ($form == null) {
			$table = $this->__table;
		} else {
			$table = $this->get_table_form($form);
		}
		$data_bind = $this->db_get($table)->result_array();
		if (count($data_bind) > 0) {
			$this->data_bind[$table] = $data_bind[0];
			$this->data_bind[$table]['id'] = $pk;
		} else {
			unset($this->data_bind[$table]);
		}
		return $this;
	}

	public function is_null()
	{
		return $this->data_bind == null || count($this->data_bind) <= 0;
	}

	public function str()
	{
		return $this->data_bind[$this->__table]['id'] . "";
	}

	public function f($str)
	{
		return $this->__table . "." . $str;
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