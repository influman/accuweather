<? 
  //***********************************************************************************************************************
  // V1.0 : Script des prévisions météo via l'API d'AccuWeather
  
  //*************************************** Messages personnels***********************************************************

  $action = getArg("action");
  $api = $_GET['api'];
  $loc = $_GET['loc'];
  // non obligatoire, pour la recherche
  $city = $_GET['city'];

  if ($action == 'search')
  {
    echo "<FORM METHOD=GET ACTION=\"https://secure.eedomus.com/script_proxy/\">";
    echo "Recherche du code localisation AccuWeather :"."<br>"."<br>";
    echo "Votre clé API AccuWeather :"."<br>";
    echo "<input id=\"exec\" type=\"hidden\" name=\"exec\" value=\"accuweather.php\">";
    echo "<input id=\"action\" type=\"hidden\" name=\"action\" value=\"search\">";
    echo "<input id=\"api\" type=\"text\" size=\"50\" name=\"api\" value=\"".$api."\">";
    echo "<br>"."<br>";
    echo "Votre ville :"."<br>";
    echo "<input id=\"city\" type=\"text\" size=\"40\" name=\"city\" value=\"".$city."\">";
    echo "<br>"."<br>";
    echo "<input type=\"submit\" value=\"Rechercher\">";
    echo "</FORM>";
	
    if ($api && $city) 
    {
      $url = "http://dataservice.accuweather.com/locations/v1/cities/FR/search?q=".$city."&apikey=".$api;
      $result = utf8_encode(httpQuery($url, 'GET'));
      $result_city = sdk_json_decode($result);

      if ($result_city) 
      {
        $loc = $result_city[0]['Key'];
        $ville = $result_city[0]['LocalizedName'];
        $dept = $result_city[0]['AdministrativeArea']['ID'];
      }
      else
      {
        //echo $result;
        var_dump($result_city);
      }
      echo "Résultat de la recherche AccuWeather :"."<br>"."<br>";
      echo "Code localisation : ".$loc." - ".$ville." ".$dept;
           
     }
     die();
  }
   
  if ($action == 'current' && $loc && $api)
  {
	 $xml = "<CURRENT>";  
	 $now = time();
	 $appelprec = 0;
	 if (loadVariable('accuCurrentPrec')) {
	 	$appelprec = loadVariable('accuCurrentPrec');
	 }
	 $difference = $now - $appelprec;
	 $diffmn = floor($difference/60);
	 if ($diffmn > 10) {
		// 10mn de tampon avant le prochain appel à l'API AccuWeather
		// 1 Appel retournant à la fois l'icône de temps et la température (2 capteurs)
		$url = "http://dataservice.accuweather.com/currentconditions/v1/".$loc."?apikey=".$api;
         	$result_current = sdk_json_decode(utf8_encode(httpQuery($url,'GET')));
		$result_current_icon = $result_current[0]['WeatherIcon'];
		$result_current_tempvalue = $result_current[0]['Temperature']['Metric']['Value'];
		$result_current_tempunit = $result_current[0]['Temperature']['Metric']['Unit'];
		//$result_current_tempvalue = $result_current[0]['Temperature']['Imperial']['Value'];
		//$result_current_tempunit = $result_current[0]['Temperature']['Imperial']['Unit'];
		saveVariable('accuCurrentPrec', $now);
		saveVariable('accuCurrentIcon', $result_current_icon);
		saveVariable('accuCurrentValue', $result_current_tempvalue);
		saveVariable('accuCurrentUnit', $result_current_tempunit);
	 } else {
		$result_current_icon = loadVariable('accuCurrentIcon');
		$result_current_tempvalue = loadVariable('accuCurrentValue');
		$result_current_tempunit = loadVariable('accuCurrentUnit');
	 }
    	 $xml .= "<ICON>".$result_current_icon."</ICON>\n";
	 $temp = $result_current_tempvalue;
	 $temp_unit = $result_current_tempunit;
	 if ($temp_unit == "F") {
	  $temp = round((($temp - 32) * 5 / 9), 1);
	 }
	 $xml .= "<TEMP>".$temp."</TEMP>\n";
	 $xml .= "</CURRENT>";
	 sdk_header('text/xml');
 	 echo $xml;
  }
  
  if ($action == 'nextday' && $loc && $api)
  {
	$tabforecast = array();
	$i = 0;
	$now = time();
	$appelprec = 0;
	$xml = "";
	if (loadVariable('accuForecastPrec')) {
	 	$appelprec = loadVariable('accuForecastPrec');
	}
	$difference = $now - $appelprec;
	$diffmn = floor($difference/60);
	if ($diffmn > 10) {
		// 10mn de tampon avant le prochain appel à l'API AccuWeather
		// 1 Appel retournant toutes les prévisions et les températures (15 capteurs)
		$url = "http://dataservice.accuweather.com/forecasts/v1/daily/5day/".$loc."?apikey=".$api;
		$result_nextday = sdk_json_decode(utf8_encode(httpQuery($url,'GET')));
		foreach($result_nextday['DailyForecasts'] as $forecasts) {
			$i = $i + 1;
			$tabforecast[$i]['DayIcon'] = $forecasts['Day']['Icon'];
			$tabforecast[$i]['NightIcon'] = $forecasts['Night']['Icon'];
			$tabforecast[$i]['TempMini'] = $forecasts['Temperature']['Minimum']['Value'];
			$tabforecast[$i]['TempMiniUnit'] = $forecasts['Temperature']['Minimum']['Unit'];
			$tabforecast[$i]['TempMaxi'] = $forecasts['Temperature']['Maximum']['Value'];
			$tabforecast[$i]['TempMaxiUnit'] = $forecasts['Temperature']['Maximum']['Unit'];
			saveVariable('accuForecastPrec', $now);
			saveVariable('accuTabForecast', $tabforecast);
		}
	} else {
			$tabforecast = LoadVariable('accuTabForecast');	
	}
	for ($i = 1; $i <= 5; $i++) {
		$xml .= "<DAY".$i.">";
		$xml .= "<JOUR>".$tabforecast[$i]['DayIcon']."</JOUR>";
		$xml .= "<NUIT>".$tabforecast[$i]['NightIcon']."</NUIT>";
		$tempmini = $tabforecast[$i]['TempMini'];
		$tempmini_unit = $tabforecast[$i]['TempMiniUnit'];
		if ($tempmini_unit == "F") {
		  	$tempmini = round((($tempmini - 32) * 5 / 9), 1);
		}
		$tempmaxi = $tabforecast[$i]['TempMaxi'];	
		$tempmaxi_unit = $tabforecast[$i]['TempMaxiUnit'];
		if ($tempmaxi_unit == "F") {
			$tempmaxi = round((($tempmaxi - 32) * 5 / 9), 1);
		}
		$xml .= "<TEMP>Max ".$tempmaxi." °C / Min ".$tempmini." °C</TEMP>";
		$xml .= "</DAY".$i.">\n";
	}
	sdk_header('text/xml');
 	echo '<root>'.$xml.'</root>';
}
?>