<?php

class BookItApi extends Extension {

	private $user = '';
	private $mode = '';
	private $categorycode = '';
	private $businesscode = '';
	private $productcode = '';
	
	/**
	* class constructor
	* 
	*
	*/
	public function __construct() {
		$config = Config::inst();
		
		$this->user = $config->get('BookItApi', 'BookItAPIUser'); 
	}

	/**
	* 	set the query mode
	*	@param (String) $mode
	*	@return null
	*/
	public function setMode($mode) {
		$this->mode = $mode;
	}

	/**
	* 	set the category code
	*	@param (String) $category_code
	*	@return null
	*/
	public function setCategoryCode($category_code) {
		$this->categorycode = $category_code;
	}

	/**
	* 	set the business code
	*	@param (String) $business_code
	*	@return null
	*/
	public function setBusinessCode($business_code) {
		$this->businesscode = $business_code;
	}

	/**
	* 	set the product code
	*	@param (String) $product_code
	*	@return null
	*/
	public function setpProductCode($product_code) {
		$this->productcode = $product_code;
	}

	/**
	* connect to the API and retrieve data
	* @param (Array) $query - parameters to pass to API 
	* @return (String) XML from API
	*/
	public function api_connect(Array $query) {

		$bookit = new BookIt(realpath('wsdl/bookit.wsdl'), '/tmp/bookitcache');
		$bookit->setAgentCode($this->bookituser);

		if(!isset($mode)) {
			// TODO: error handling
			echo 'Mode not set for: ' . __function__;
			return;
		}

		switch($mode) {
			case 'business':

				return $bookit->getBusiness($this->categorycode, $this->businesscode, $query);

			break;

			case 'businessesincategory':

				return $bookit->getBusinessesInCategory($this->categorycode, $query);

			break;

			case 'product':

				return $bookit->getProduct($this->categorycode, $this->businesscode, $this->productcode, $query);

			break;

			default:
				// TODO: error handling
				echo 'invalid mode for ' . __function__;
				return false;
		}
	}

	/**
	* wrapper method for api_connect
	* 
	*/
}