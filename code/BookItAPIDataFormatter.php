<?php
/*
extension class for BookItAPI
- provides formatting methods for data returned by BookIt class
*/

class BookItAPIDataFormatter extends Extension {
	
	/*
	@param Array $data - a single result from BookIt->getBusiness()
	@return String (HTML)
	*/
	public static function formatBusinessDataAsTable(Array $data) {
		// basic array format validation 
		if(!isset($data['business']['name']) 
			or 
			!isset($data['business']['code'])
			or
			!isset($data['products'][0]['availability']['days'])
			or 
			!isset($data['products'])
			) {
			return false;
		}

		$html = '<table border=1>';
		$html .= '<thead><tr><th>&nbsp;</th>';
		// counter for days outputted
		$counter = 1;
		foreach ($data['products'][0]['availability']['days'] as $day) {
			// we only want a week
			if($counter < 8) {
				$html .= '<th>' . $day['date']. '</th>';
				$counter ++;	
			}
		}

		$html .= '</tr></thead><tbody>';

		// rows
		foreach($data['products'] as $product) {
			// product
			$html .= '<tr><td>' . $product['info']['name'] . '</td>';
			// days
			$counter = 1;
			foreach ($product['availability']['days'] as $day) {

				if($counter < 8) {

					$html .= '<td>';

					if($day['available'] == 'N') {
						$html .= '-';
					}
					else {
						$html .= '<a href="'.$product['info']['bookingurl'].'&di='.$day['date'].'&do='.$day['date'].'">' . $day['retailrate'] . '</a>' ;	
					}
				
					$html .= '</td>';
					$counter ++;
				}
			}

			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		return $html;
	}

}