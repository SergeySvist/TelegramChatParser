<?php

function getUserIp(){
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
}

function getUserAgent(){
    return $_SERVER['HTTP_USER_AGENT'];
}

function getUserLocation(): string
{
    $ip = getUserIp();
    $json = json_decode(file_get_contents("https://ipinfo.io/$ip/geo"), true);
    if (isset($json['country']) && isset($json['region']) && isset($json['city']))
        return $json['country'] . " " . $json['region'] . " " . $json['city'];
    else
        return 'UNKNOWN';
}