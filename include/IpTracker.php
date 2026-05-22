<?php
function ip_visitor_country()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];
    $country  = "Unknown";

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.geoplugin.net/json.gp?ip=".$ip);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $ip_data_in = curl_exec($ch); 
    curl_close($ch);

    $ip_data = json_decode($ip_data_in,true);
    $ip_data = str_replace('&quot;', '"', $ip_data); 

    if($ip_data && isset($ip_data['geoplugin_countryName']) && $ip_data['geoplugin_countryName'] != null) {
        $country = $ip_data['geoplugin_countryName'];
    }

    return $country;
}


if (isset($_SESSION["countryId"])) {

    $countryID = $_SESSION["countryId"];
}else{
    $countryID =0;
}
if($countryID>0){

}else{

    $CountryNamewithWild = ip_visitor_country();
    $db = new Database();
    $query_check_contry_id = $db->getRow('SELECT * FROM country WHERE name  = ?', [$CountryNamewithWild]);
    if($query_check_contry_id > 0){
        $_SESSION["countryId"] = $query_check_contry_id['pk_id'];
        $_SESSION["countryName"] = $query_check_contry_id['name'];

        $check_Cities_query = $db->getRow('SELECT count(id) as count FROM city_master WHERE countryId = ?', [$query_check_contry_id['pk_id']]);
        if($check_Cities_query['count']>0){
            $_SESSION["is_Cities"] = true; 
        }else{
            $_SESSION["is_Cities"] = false; 
        }
    }

}

?>