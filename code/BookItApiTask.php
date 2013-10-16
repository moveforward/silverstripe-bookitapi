<?php

class BookItApiTask extends BuildTask{
	
	protected $title = "Test BookItAPI";
	protected $description = "Pull a set of BookIt API results";


	// controller action to be run by default
	function run($request) {

		$b = new BookItAPI();
		$b->setCategoryCode('123');
		$b->setMode('business');


		$query_attributes = array(
			'category' => 6, 
			'location' => 363,
			'modified_since' => date('Y-m-d h:i:s', strtotime('-30 days')),
			'created_since' => date('Y-m-d h:i:s', strtotime('-30 days'))
			); 

		$results = $b->api_connect($query_attributes); 

		echo count($results) . ' results returned'; 

		foreach ($results as $result) {
			print_r($result);
		}

	}
}