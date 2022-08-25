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

 function buy_player()
 {
    echo 'ciao';
 }

 function insert_player($conn, $internalId, $externalId, $nome, $squadra, $qta, $manager, $payed)
 {
    $result = pg_query($conn, "INSERT INTO " . PLAYERS_TABLE .
                              " VALUES($internalId,
                                       $externalId,
                                       '$nome',
                                       '$squadra',
                                       $qta,
                                       $manager,
                                       $payed)"
                      );
 }
?>