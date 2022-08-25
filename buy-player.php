<?php
 include_once 'config.php';
 include_once 'functions.php';

 $dbConn = createConnection(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);

 delete_table($dbConn, PLAYERS_TABLE);
 create_table($dbConn, PLAYERS_TABLE, [
    "internal_id" => "INT",
    "external_id" => "INT",
    "nome" => "VARCHAR(200)",
    "squadra" => "VARCHAR(100)",
    "qta" => "INT",
    "manager" => "INT",
    "payed" => "INT"
 ]);

 delete_table($dbConn, MANAGERS_TABLE);
 create_table($dbConn, MANAGERS_TABLE, [
    "internal_id" => "INT",
    "nome" => "VARCHAR(100)",
    "credits" => "INT"
 ]);
 insert_player($dbConn, 1, 1, "Valerios", "Toro", 13, 4, 16);
?>