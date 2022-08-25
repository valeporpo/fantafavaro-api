<?php
 function createConnection($dbHost, $dbName, $dbUser, $dbPassword)
 {
    $connInfo = [
        "host" => $dbHost,
        "dbname" => $dbName,
        "user" => $dbUser,
        "password" => $dbPassword
    ];
    $connString = assembleInfo($connInfo);

    $conn = pg_connect($connString)
              or die('Could not connect: ' . pg_last_error());

    return $conn;
 }

 function assembleInfo($arr)
 {
    $string = "";

    foreach($arr as $key => $value)
    {
      $string .= $key . "=" . $value . " ";
    }

    return $string;
 }
?>