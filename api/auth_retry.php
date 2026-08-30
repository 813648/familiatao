<?php
function http_post($url, $payload, $headers = [], $cookieFile = '') {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));
  if ($cookieFile) {
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
  }
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return ['code' => $httpCode, 'body' => $response];
}

function perform_login($cookieFile) {
  $loginUrl = __DIR__ . "/login-teste.php"; // chamada local
  $ch = curl_init("https://familiatao.ct.ws/api/login-teste.php");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
  curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return $httpCode === 200;
}

function api_post_with_retry($url, $payload, $cookieFile = __DIR__ . '/cookiejar.txt') {
  $res = http_post($url, $payload, [], $cookieFile);
  if (in_array($res['code'], [401, 403])) {
    if (perform_login($cookieFile)) {
      $res = http_post($url, $payload, [], $cookieFile);
    }
  }
  return $res;
}
