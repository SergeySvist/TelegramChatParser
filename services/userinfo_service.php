<?php

function getUserIp(){
    return $_SERVER['REMOTE_ADDR'];
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