<?php
 function checkParams($userInput, $requiredParams)
 {
    for($i=0; $i<count($requiredParams); $i++)
    {
        if(!array_key_exists($requiredParams[$i], $userInput))
        {
            //echo 'Some required params are missing';
            return false;
        }
    }
    return true;
 }

 function checkMethod($allowedMethod)
 {
   if($allowedMethod !== $_SERVER['REQUEST_METHOD'])
   {
      echo 'Method not allowed';
      exit;
   }
 }
 
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
              or die('Could not connect: ' . print_r(error_get_last()));

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

 function delete_table($conn, $table)
 {
    pg_query($conn, "DROP TABLE IF EXISTS " . $table);
 }

 function create_table($conn, $table, $columns)
 {
    $columnsComponent = "(";
    foreach($columns as $name => $type)
    {
       $columnsComponent .= $name . " " . $type;
       $columnsComponent .= ($name !== array_key_last($columns)) ? "," : "";
    }
    $columnsComponent .= ")";
    $result = pg_query($conn, "CREATE TABLE " . $table . " " . $columnsComponent);
 }
?>