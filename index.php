<?php
header('Access-Control-Allow-Origin: *');
include_once 'config/config.php';
include_once 'functions/functions.php';
include_once 'functions/api_calls.php';

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = substr(
               $requestUri,
               0,
               strpos($requestUri, '?')
            );
$queryString = substr(
                  $requestUri,
                  strpos($requestUri, '?') + 1
               );

$endPoint = end(explode("/", $basePath));
parse_str($queryString, $queryArray);


if(!isset($queryArray['token']))
{
    echo 'Access token is required';
    exit;
} else if($queryArray['token'] != ACCESS_KEY)
{
    echo 'Access token you provided is not valid';
    exit;
} else if(!function_exists($endPoint))
{
    echo 'Unknown method';
    exit;
} else
{
  $dbConn = createConnection(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
  unset($queryArray['token']);
  $endPoint($dbConn, $queryArray);
}

?>