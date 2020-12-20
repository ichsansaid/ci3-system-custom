<?php

abstract class Middleware{
	protected $controller;
	protected $params;

	public function __construct($controller, $params)
	{
		$this->params = $params;
		$this->controller = $controller;
	}
	abstract function run() : bool;
	public function post_run($sucess){
		return true;
	}
	public function getParams(){
		return $this->params;
	}
}
