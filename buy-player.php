<?php
 include_once 'config.php';
 include_once 'functions.php';

 $dbConn = createConnection(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);

 // Prepare a query for execution
 pg_query($dbConn, "DROP TABLE players"); 
 $result = pg_query($dbConn, "CREATE TABLE players (internal_id INT,
                                                    external_id INT,
                                                    nome VARCHAR(200),
                                                    squadra VARCHAR (100),
                                                    qta INT,
                                                    manager INT,
                                                    payed INT
                                                   )"
                   );

// Execute the prepared query.  Note that it is not necessary to escape
// the string "Joe's Widgets" in any way
 //$result = pg_execute($dbConn, "my_query");
/*
// Execute the same prepared query, this time with a different parameter
$result = pg_execute($dbconn, "my_query", array("Clothes Clothes Clothes"));*/
?>