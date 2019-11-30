<?php
header("Content-type:text/html;charset=utf-8");

//模拟Get请求
function httpGet($url)
{
    $ch = curl_init();
    $ip = rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
    $UserAgent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)";
    $headers = ['X-FORWARDED-FOR:' . $ip . '', 'CLIENT-IP:' . $ip . ''];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//获取真实IP
function get_real_ip()
{
  $ip = $_SERVER['REMOTE_ADDR'];
  if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
    foreach ($matches[0] AS $xip) {
        if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
            $ip = $xip;
            break;
        }
    }
  } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
  } elseif (isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_X_REAL_IP'])) {
    $ip = $_SERVER['HTTP_X_REAL_IP'];
  }
  return $ip;
}

//获取IP归属地
function get_ip_city($ip='')
{
  $ip = $ip?$ip:get_real_ip();
  $url = 'http://www.ip138.com/ips138.asp?ip=' . $ip . '&action=2';
  $data = httpGet($url);
  $data = mb_convert_encoding($data, "UTF-8", "GB2312");
  preg_match('!本站数据：(.*?)</li>!i', $data, $location);
  $location = $location[1]?$location[1]:'';
  return trim($location);
}