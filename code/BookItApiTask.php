<?php

class BookItApiTask extends BuildTask{
	
	protected $title = "Test BookItAPI";
	protected $description = "Pull a set of BookIt API results";


	// controller action to be run by default
	function run($request) {

		$b = new BookItAPI();
		$b->setMode('businessesincategory');
		// no need to set category code as we can query without it
		// just test api connection and data return

		$query_attributes = array(
			'MajorRegionCode' => 'WEL'
		); 

		$results = $b->api_connect($query_attributes); 

		echo '<h4>Testing getBusinessesByCategory</h4>';

		$r_count = count($results['businesses']);

		if($r_count > 0) {
			echo '<p>' . count($results['businesses']) . ' results</p>';

			foreach ($results['businesses'] as $key => $value) {
				echo '<p>'.$value['business']['name'].'</p>';
			}
		}
		else {
			echo '<p>No results returned. Check your API key.</p>';			
		}

		echo '<h4>Testing getBusiness</h4>';

		$b->setMode('business');
		$b->setBusinessCode('KINWGN');

		$results = $b->api_connect(array());

		echo '<pre>'.print_r($results, 1).'</pre>';
	}
}