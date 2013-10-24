<?php

class BookItException extends Exception {}

class BookIt extends Extension {
  /**
   * Agent code that must be submitted with every request
   *
   * @var string
   */
  private $agentCode;
  /**
   * Search type
   *
   * @var string
   */
  private $searchType;
  /**
   * Cache for SOAP calls
   *
   * @var BookItCache
   */
  protected $cache;
  /**
   * SOAP client
   *
   * @var SoapClient
   */
  protected $client;

  public function __construct($wsdlLocation, $cacheDir) {
    //error_log("Bookit Class instantiated - Bookit.php - line 33", 0);
    $this->cache = new BookItCache();
    $this->cache->setCacheDir($cacheDir);
    $this->client = new SoapClient($wsdlLocation, array('trace' => true, 'uri' => ''));
  }

  /**
   * Return a listing of products in the specififed category
   *
   * Possible other parameters:
   *    DateIn
   *    DateOut
   *    DaysRequired
   *    MajorRegionCode
   *    MinorRegionCode
   *    PIMCode
   *    MinPrice
   *    MaxPrice
   *    SortOrder
   *
   * @param string $categoryCode
   * @param array $params array of other parameters (see above)
   */
  public function getBusinessesInCategory($categoryCode, $params = array()) {

    //error_log("function getBusinessesInCategory() - Bookit.php - line 57", 0);
    $optionalParams = array(
      'DateIn',
      'DateOut',
      'DaysRequired',
      'MajorRegionCode',
      'MinorRegionCode',
      'PIMCode',
      'MinPrice',
      'MaxPrice',
      'SortOrder'
    );

    

    if (!is_array($params)) {
      
      throw new BookItException('$params must be an array');
    }

    

    // Make sure nothing funny was submitted
    foreach($params as $name => $value) {
      if (!in_array($name, $optionalParams)) {
        
        throw new BookItException($name . ' is not a valid parameter for this method');
      }
    }

    

    // Fill non-supplied optional parameters with blank strings
    foreach($optionalParams as $name) {
      if (!isset($params[$name])) {
        $params[$name] = '';
      }
    }



    $params['AgentCode'] = $this->agentCode;
    $params['CategoryCode'] = $categoryCode;

    // print_r($params);

    

    $cacheName = sha1(serialize($params));
    
    // if (!($returnData = $this->cache->fetch($cacheName))) {
    if (!false) {
      try {
        
        $this->client->GetBusinessesInCategory($params);
      } 
      catch (SoapFault $soapException) {
        
        die('<h4 style="margin-top:10px;">Sorry, there was an error with that search.</h4>');
      }

      $dom = simplexml_load_string(str_ireplace('SOAP:', '', $this->client->__getLastResponse()));
      $dom = $dom->Body->GetBusinessesInCategoryResponse->GetBusinessesInCategoryResult->getbusinessesincategory;

      // print_r($dom);

      
      
      //error_log("END - this->client-> - Bookit.php - line 110", 0);
      $businesses = array();
      foreach ($dom->businesses->business as $business) {

        

        $thisBusiness = array(
          'name' => (string) $business->name,
          'bookingurl' => (string) $business->bookingurl,
          'infourl' => (string) $business->bookingurl,
          'code' => (string) $business->code,
          'logo' => (string) $business->logo,
          'summary' => (string) $business->summary,
          'minorregion' => (string) $business->minorregion,
          'majorregion' => (string) $business->majorregion,
          'country' => (string) $business->country,
          'address' => (string) $business->address,
          'rates' => (object) $business->rate,
        );

        $thisImage = array();

        if ($business->image && count($business->image)) {
          $thisImage['url'] = (string) $business->image->url;
          $thisImage['credit'] = (string) $business->image->credit != '[None]' ? (string) $business->image->credit : '';
          $thisImage['caption'] = (string) $business->image->caption != '[Untitled Image]' ? (string) $business->image->caption : '';
          $thisImage['thumb'] = (string) $business->image->optimised->thumb;
          $thisImage['60x45'] = (string) $business->image->optimised->image60x45;
          $thisImage['80x60'] = (string) $business->image->optimised->image80x60;
          $thisImage['130x98'] = (string) $business->image->optimised->image130x98;
          $thisImage['400x300'] = (string) $business->image->optimised->image400x300;
          $thisImage['400x300-best'] = (string) $business->image->optimised->image400x300best;
        }

        if ($business->availabilitybyday && count($business->availabilitybyday->day)) {

          

          $availability = array(
            'attributes' => array(''),
            'days' => array()
          );
          foreach ($business->availabilitybyday->day as $day) {

            

            $availability['days'][] = array(
              'date' => (string) $day['date'],
              'ratename' => (string) $day['ratename'],
              'selected' => (string) $day['selected'],
              'available' => (string) $day['available'],
              'ratetype' => (string) $day['ratetype'],
              'normalrate' => (string) $day['normalrate'],
              'retailrate' => (string) $day['retailrate']
            );
          }
        } else {

          
          $availability = FALSE;
        }

        $businesses[] = array(
          'business' => $thisBusiness,
          'image' => $thisImage,
          'availability' => $availability
        );
      }

     //  $this->cache->save($cacheName, $returnData, 3600 * 6);
    }
    else {
      
    }

    $days = array();
    
    if (isset($dom->businesses->availabilitybyday->day)) {

      foreach($dom->businesses->availabilitybyday->day as $day) {
        list($d, $m, $y) = explode('/', (string) $day['date']);
        $days[] = strtotime("$m/$d/$y");
      }
    }

    $returnData = array(
      'businesses' => $businesses,
      'days' => $days
    );

    // $this->cache->save($cacheName, $returnData, 3600 * 6);

    return $returnData;
  }

  /**
   * Returns full product information
   *
   * Possible other parameters:
   *    DateIn
   *    DateOut
   *    DaysRequired
   *
   * @param string $categoryCode
   * @param string $businessCode
   * @param array $params array of other parameters (see above)
   */
  public function getBusiness($categoryCode, $businessCode, $params = array()) {
    $optionalParams = array(
      'DateIn',
      'DateOut',
      'DaysRequired',
    );

    if (!is_array($params)) {
      throw new BookItException('$params must be an array');
    }

    // Make sure nothing funny was submitted
    foreach($params as $name => $value) {
      if (!in_array($name, $optionalParams)) {
        throw new BookItException($name . ' is not a valid parameter for this method');
      }
    }

    // Fill non-supplied optional parameters with blank strings
    foreach($optionalParams as $name) {
      if (!isset($params[$name])) {
        $params[$name] = '';
      }
    }

    $params['AgentCode'] = $this->agentCode;
    $params['CategoryCode'] = $categoryCode;
    $params['BusinessCode'] = $businessCode;

    $cacheName = sha1(serialize($params));
    $cacheData = $this->cache->fetch($cacheName);
    if ($cacheData === false) {
      try {
        $result = $this->client->GetBusiness($params);
      } catch (SoapFault $soapException) {
        throw new BookItException($soapException->getMessage(), $soapException->getCode());
      }

      $dom = simplexml_load_string(str_ireplace('SOAP:', '', $this->client->__getLastResponse()));
      $dom = $dom->Body->GetBusinessResponse->GetBusinessResult->getbusiness;

      $business = array(
        'name' => (string) $dom->business->name,
        'code' => (string) $dom->business->code,
        'logo' => (string) $dom->business->logo,
        'summary' => (string) $dom->business->summary,
        'minorregion' => (string) $dom->business->minorregion,
        'majorregion' => (string) $dom->business->majorregion,
        'country' => (string) $dom->business->country,
        'address' => (string) $dom->business->address,
        'description' => (string) $dom->business->description
      );

      $images = array();
      if ($dom->business->images && count($dom->business->images->image)) {
        foreach($dom->business->images->image as $image) {
          $thisImage = array();
          $thisImage['url'] = (string) $image->url;
          $thisImage['credit'] = (string) $image->credit != '[None]' ? (string) $image->credit : '';
          $thisImage['caption'] = (string) $image->caption != '[Untitled Image]' ? (string) $image->caption : '';
          $thisImage['thumb'] = (string) $image->optimised->thumb;
          $thisImage['60x45'] = (string) $image->optimised->image60x45;
          $thisImage['80x60'] = (string) $image->optimised->image80x60;
          $thisImage['130x98'] = (string) $image->optimised->image130x98;
          $thisImage['400x300'] = (string) $image->optimised->image400x300;
          $thisImage['400x300-best'] = (string) $image->optimised->image400x300best;
          $images[] = $thisImage;
        }
      }


      $products = array();
      if ($dom->business->products && count($dom->business->products->product)) {
        foreach($dom->business->products->product as $product) {
          $info = array();
          $availability = array();
          $images = array();

          $info['name'] = (string) $product->name;
          $info['code'] = (string) $product->code;
          $info['description'] = (string) $product->description;
          $info['bookingurl'] = (string) $product->bookingurl;

          if ($product->images && count($product->images->image)) {
            foreach($product->images->image as $image) {
              $thisImage = array();
              $thisImage['url'] = (string) $image->url;
              $thisImage['credit'] = (string) $image->credit != '[None]' ? (string) $image->credit : '';
              $thisImage['caption'] = (string) $image->caption != '[Untitled Image]' ? (string) $image->caption : '';
              $thisImage['thumb'] = (string) $image->optimised->thumb;
              $thisImage['60x45'] = (string) $image->optimised->image60x45;
              $thisImage['80x60'] = (string) $image->optimised->image80x60;
              $thisImage['130x98'] = (string) $image->optimised->image130x98;
              $thisImage['400x300'] = (string) $image->optimised->image400x300;
              $thisImage['400x300-best'] = (string) $image->optimised->image400x300best;
              $images[] = $thisImage;
            }
          }

          if ($product->availabilitybyday) {
            $availability = array(
              'attributes' => array(
                'datein' => (string) $product->availabilitybyday['datein'],
                'dateout' => (string) $product->availabilitybyday['dateout'],
              ),
              'days' => array()
            );

            if (count($product->availabilitybyday->day)) {
              foreach($product->availabilitybyday->day as $day) {
                $availability['days'][] = array(
                  'date' => (string) $day['date'],
                  'ratename' => (string) $day['ratename'],
                  'selected' => (string) $day['selected'],
                  'available' => (string) $day['available'],
                  'ratetype' => (string) $day['ratetype'],
                  'normalrate' => (string) $day['normalrate'],
                  'retailrate' => (string) $day['retailrate']
                );
              }
            }
          } else {
            $availability = false;
          }

          $products[] = array(
            'info' => $info,
            'availability' => $availability,
            'images' => $images
          );
        }
      }


      $days = array();
      if ($dom->business->availabilitybyday->day) {
        foreach($dom->business->availabilitybyday->day as $day) {
          list($d, $m, $y) = explode('/', (string) $day['date']);
          $days[] = strtotime("$m/$d/$y");
        }
      }

      // Process data
      $returnData = array(
        'business' => $business,
        'images' => $images,
        'products' => $products,
        'days' => $days
      );

      $this->cache->save($cacheName, $returnData, 600);
    } else {
      $returnData = $cacheData;
    }
    return $returnData;
  }

  /**
   * Returns product information about one product
   *
   * @param string $categoryCode
   * @param string $businessCode
   * @param string $productCode
   * @param array $params array of other parameters (see above)
   */
  public function getProduct($categoryCode, $businessCode, $productCode, $params = array()) {
    $optionalParams = array(
      'DateIn',
      'DateOut',
      'DaysRequired',
    );

    if (!is_array($params)) {
      throw new BookItException('$params must be an array');
    }

    // Make sure nothing funny was submitted
    foreach($params as $name => $value) {
      if (!in_array($name, $optionalParams)) {
        throw new BookItException($name . ' is not a valid parameter for this method');
      }
    }

    // Fill non-supplied optional parameters with blank strings
    foreach($optionalParams as $name) {
      if (!isset($params[$name])) {
        $params[$name] = '';
      }
    }

    $params['AgentCode'] = $this->agentCode;
    $params['CategoryCode'] = $categoryCode;
    $params['BusinessCode'] = $businessCode;

    $cacheName = sha1(serialize($params)).'-'.md5($productCode).'-'.'-product-info';
    $cacheData = $this->cache->fetch($cacheName);
    if ($cacheData === false) {
      try {
        $this->client->GetBusiness($params);
      } catch (SoapFault $soapException) {
        throw new BookItException($soapException->getMessage(), $soapException->getCode());
      }

      $dom = simplexml_load_string(str_ireplace('SOAP:', '', $this->client->__getLastResponse()));
      $dom = $dom->Body->GetBusinessResponse->GetBusinessResult->getbusiness;

      $business = array(
        'name' => (string) $dom->business->name,
        'code' => (string) $dom->business->code,
        'logo' => (string) $dom->business->logo,
        'summary' => (string) $dom->business->summary,
        'minorregion' => (string) $dom->business->minorregion,
        'majorregion' => (string) $dom->business->majorregion,
        'country' => (string) $dom->business->country,
        'address' => (string) $dom->business->address,
        'description' => (string) $dom->business->description
      );

      $images = array();
      if ($dom->business->images && count($dom->business->images->image)) {
        foreach($dom->business->images->image as $image) {
          $thisImage = array();
          $thisImage['url'] = (string) $image->url;
          $thisImage['credit'] = (string) $image->credit != '[None]' ? (string) $image->credit : '';
          $thisImage['caption'] = (string) $image->caption != '[Untitled Image]' ? (string) $image->caption : '';
          $thisImage['thumb'] = (string) $image->optimised->thumb;
          $thisImage['60x45'] = (string) $image->optimised->image60x45;
          $thisImage['80x60'] = (string) $image->optimised->image80x60;
          $thisImage['130x98'] = (string) $image->optimised->image130x98;
          $thisImage['400x300'] = (string) $image->optimised->image400x300;
          $thisImage['400x300-best'] = (string) $image->optimised->image400x300best;
          $images[] = $thisImage;
        }
      }


      $product = FALSE;
      $finalProduct = array();
      if ($dom->business->products && count($dom->business->products->product)) {
        foreach($dom->business->products->product as $product) {
          if ((string) $product->code == $productCode) {
            $info = array();
            $availability = array();
            $images = array();

            $info['name'] = (string) $product->name;
            $info['code'] = (string) $product->code;
            $info['description'] = (string) $product->description;
            $info['bookingurl'] = (string) $product->bookingurl;

            if ($product->images && count($product->images->image)) {
              foreach($product->images->image as $image) {
                $thisImage = array();
                $thisImage['url'] = (string) $image->url;
                $thisImage['credit'] = (string) $image->credit != '[None]' ? (string) $image->credit : '';
                $thisImage['caption'] = (string) $image->caption != '[Untitled Image]' ? (string) $image->caption : '';
                $thisImage['thumb'] = (string) $image->optimised->thumb;
                $thisImage['60x45'] = (string) $image->optimised->image60x45;
                $thisImage['80x60'] = (string) $image->optimised->image80x60;
                $thisImage['130x98'] = (string) $image->optimised->image130x98;
                $thisImage['400x300'] = (string) $image->optimised->image400x300;
                $thisImage['400x300-best'] = (string) $image->optimised->image400x300best;
                $images[] = $thisImage;
              }
            }

            if ($product->availabilitybyday) {
              $availability = array(
                'attributes' => array(
                  'datein' => (string) $product->availabilitybyday['datein'],
                  'dateout' => (string) $product->availabilitybyday['dateout'],
                ),
                'days' => array()
              );

              if (count($product->availabilitybyday->day)) {
                foreach($product->availabilitybyday->day as $day) {
                  $availability['days'][] = array(
                    'date' => (string) $day['date'],
                    'ratename' => (string) $day['ratename'],
                    'selected' => (string) $day['selected'],
                    'available' => (string) $day['available'],
                    'ratetype' => (string) $day['ratetype'],
                    'normalrate' => (string) $day['normalrate'],
                    'retailrate' => (string) $day['retailrate']
                  );
                }
              }
            } else {
              $availability = false;
            }

            $finalProduct = array(
              'info' => $info,
              'availability' => $availability,
              'images' => $images
            );
          }
        }
      }

      // Process data
      $returnData = array(
        'business' => $business,
        'images' => $images,
        'product' => $finalProduct
      );

      $this->cache->save($cacheName, $returnData, 600);
    } else {
      $returnData = $cacheData;
    }
    return $returnData;
  }

  public function setAgentCode($agentCode) {
    if ($agentCode == '') {
      throw new BookItException('agentCode must not be blank');
    }
    $this->agentCode = $agentCode;
  }

  public function setSearchType($searchType) {
    $this->searchType = $searchType;
  }

  public function getSearchType() {
    return $this->searchType;
  }

  public function getAgentCode($agentCode) {
    return $this->agentCode;
  }
}

// Convert from a date to a timestamp
function nz2ts($nzDate) {
  list($d, $m, $y) = explode('/', $nzDate);
  return strtotime("$m/$d/$y");
}