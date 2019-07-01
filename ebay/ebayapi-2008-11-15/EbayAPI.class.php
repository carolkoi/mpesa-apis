<?php 
/**
 * 	EbayAPI.class.php
 * 
 *	A class to interface with the Ebay Developer's API to access functions
 * 
 *	@author:	A.D.Surrey
 *	@version:	1.0
 * 
 */
//error_reporting(E_ALL);

define("USER_ID" , "testuser_sammymwa");
define("RESPONSE_ENCODING", "XML");
define("PARTNER_CODE", "");
define("TRACKING_ID", "");
define("SITE_ID", "");
define("APP_ID", "SammyWaw-LetaBoxT-SBX-48e35c535-7d347f0b");
define("CATEGORY_ID", "");

class EbayAPI
{
	private	$endPoint,
			$xmlResponse,		// result of Simplexml_load_file, Ebay response
			$bigPicUrl;
	
	
	public function EbayAPI()
	{
		$this->endPoint = "http://open.api.ebay.com/shopping";
	}
	
	/**
	 * Search Ebay using keywords as a query
	 *
	 * @param	array 	$keywords - Words to search with
	 * @param	int		$maxResults - Max amout of results to show
	 * @param	bool	$fixedPriceOnly - show fixed price items or all items?
	 * @return	XML output
	 */
	public function getSearchResults($keywords, $maxResults, $fixedPriceOnly = false)
	{
		$keywords = urlencode(htmlentities(strip_tags(trim($keywords)))); //remove html from query
		$priceRangeMax = 500;
		$priceRangeMin = 0.0;
		$itemType = "";
		
		if($fixedPriceOnly)
		{
			$itemType = "FixedPricedItem";
		}
		else
		{
			$itemType = "AllItems";
		}
		
		// Construct the FindItems call 
        $apicall = $this->endPoint."?callname=FindItemsAdvanced"
                 . "&version=537"
                 . "&siteid=". SITE_ID
                 . "&appid=". APP_ID
                 . "&QueryKeywords=$keywords"
                 . "&MaxEntries=$maxResults"
                 . "&ItemSort=EndTime"
                 . "&ItemType=". $itemType		// AllItemTypes or AllItems
                 . "&PriceMin.Value=$priceRangeMin"
                 . "&PriceMax.Value=$priceRangeMax"
                 . "&IncludeSelector=SearchDetails"  
                 . "&trackingpartnercode=9"
                 . "&trackingid=". TRACKING_ID
                 . "&affiliateuserid=".USER_ID
                 . "&CategoryID=". CATEGORY_ID
                 . "&responseencoding=". RESPONSE_ENCODING;
                 
                 //try:
                 //SortOrder=Ascending&MaxEntries
                 
                 $this->xmlResponse = simplexml_load_file($apicall);
                 
                //print_r($this->xmlResponse);
                 
                 if ($this->xmlResponse && $this->xmlResponse->TotalItems > 0)
                 {
                 	foreach($this->xmlResponse->SearchResult->ItemArray->Item as $item)
                 	{
                 		$link  = $item->ViewItemURLForNaturalSearch;
                		$title = $item->Title;
                 		
                 		if($item->GalleryURL) 
                 		{
                    		$thumbURL = $item->GalleryURL;
                		} 
                		else 
                		{
                    		$thumbURL = "img/pic.gif";
               			}
               			
               			$price = sprintf("%01.2f", $item->ConvertedCurrentPrice);
               			
               			/*
                		$ship  = sprintf("%01.2f", $item->ShippingCostSummary->ShippingServiceCost);
                		$total = sprintf("%01.2f", ((float)$item->ConvertedCurrentPrice 
                                          + (float)$item->ShippingCostSummary->ShippingServiceCost));
                        */
               			                 
						// Determine currency to display - so far only seen cases where priceCurr = shipCurr, but may be others
                		
						/*
						$priceCurr = (string) $item->ConvertedCurrentPrice['currencyID'];
                		$shipCurr  = (string) $item->ShippingCostSummary->ShippingServiceCost['currencyID'];
                		
                		if ($priceCurr == $shipCurr) 
                		{
                    		$curr = $priceCurr;
                		} 
                		else 
                		{
                    		$curr = "$priceCurr / $shipCurr";  // potential case where price/ship currencies differ
                		}
    					*/
                		
						$timeLeft = $this->getPrettyTimeFromEbayTime($item->TimeLeft); 
                		//$endTime = strtotime($item->EndTime);   // returns Epoch seconds
                		//$endTime = $item->EndTime;
                		
                		/**
                		 * Here you echo out the info for each result  /////////////////////////////////////
                		 */
                		echo 'Price: ' .$price;
						echo 'Title: ' .$title;
						echo 'url: ' .$link;
                		
                 	}//foreach
                 }
                 else
                 {
                 	//echo "Sorry, No Results.";
                 }
                 
	}
	
	/**
	 *	Get details for a particular item, pic etc
	 *	@param	$id - Id of item
	 */
	public function getItemDetails($id)
	{
		$apicall = $this->endPoint. "?callname=GetSingleItem&version=515"
                 . "&appid=eBay3e085-a78c-4080-ac24-a322315e506&ItemID=$id"
                 . "&responseencoding=" .RESPONSE_ENCODING
                 . "&IncludeSelector=ShippingCosts,Details"; 
                 
    	$this->xmlResponse = simplexml_load_file($apicall);
    	
    	//print_r($this->xmlResponse);

		if ($this->xmlResponse->Item->PictureURL) 
		{
        	$this->bigPicUrl = $this->xmlResponse->Item->PictureURL;
        } else 
        {
        	$this->bigPicUrl = "img/pic.gif";
        }
	}
	
	/**
	 * Return the most popular items in a group
	 *
	 */
	public function getMostPopularItems($category, $numResults)
	{
		
	}
	
	/**
	 * Get the picture url for an item, after getItemDetails is called!
	 *
	 * @return url
	 */
	public function getBigPicUrl()
	{
		return $this->bigPicUrl;
	}
	
	/**
	 * Clean input, escape slashes
	 */
	private function clean($str)
	{
		if(get_magic_quotes_gpc())
		{
			$str = stripslashes($str);
		}
		// Let MySQL remove nasty characters.
		$str = mysql_real_escape_string($str);
		return $str;
	}
	
	private function getPrettyTimeFromEbayTime($eBayTimeString)
	{
    	// Input is of form 'PT12M25S'
    	$matchAry = array(); // initialize array which will be filled in preg_match
    	$pattern = "#P([0-9]{0,3}D)?T([0-9]?[0-9]H)?([0-9]?[0-9]M)?([0-9]?[0-9]S)#msiU";
    	preg_match($pattern, $eBayTimeString, $matchAry);
    
    	$days  = (int) $matchAry[1];
    	$hours = (int) $matchAry[2];
    	$min   = (int) $matchAry[3];    // $matchAry[3] is of form 55M - cast to int 
    	$sec   = (int) $matchAry[4];
    
		$timeRemain = array();
	
		$timeRemain[0] = sprintf("%02d", $days); 
		$timeRemain[1] = sprintf("%02d", $hours); 
		$timeRemain[2] = sprintf("%02d", $min); 
		$timeRemain[3] = sprintf("%02d", $sec); 
		
		// only make red uptil the first none 00 value
		foreach($timeRemain as $key => &$val)
		{
			if($val == '00')
			{
				$val = '<span class="redTime">00</span>';
			}
			else break;
		}
		
		//print_r($timeRemain);
		$retnStr = $timeRemain[0]."d ". $timeRemain[1] ."h ". $timeRemain[2] ."m ". $timeRemain[3] . "s";
	
		/*
    	$retnStr = '';
    	if ($days)  { $retnStr .= "$days" . "d";  } else {$retnStr .= "0d"; }
    	if ($hours) { $retnStr .= " $hours" . "h"; } else {$retnStr .= " 0h"; }
    	if ($min)   { $retnStr .= " $min". "m";   } else {$retnStr .= " 0m"; }
    	if ($sec)   { $retnStr .= " $sec" ."s";   } else {$retnStr .= " 0s"; }
    	*/
	
    	return $retnStr;
	} // function

	private function pluralS($intIn) 
	{
    	// if $intIn > 1 return an 's', else return null string
    	if ($intIn > 1) 
    	{
        	return 's';
    	} 
    	else 
    	{
        	return '';
    	}
	} // function
}
?>