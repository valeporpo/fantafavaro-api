<?php 
 
 function start_game($conn, $params)
 {

    if(count($params) < 2)
    {
       echo 'Specify at least two managers';
       exit;
    }

    $paramsKeys = array_keys($params);

    for($i = 0; $i < count($paramsKeys); $i++)
    {
       if(!is_numeric($paramsKeys[$i]))
       {
          echo 'All parameters must be numeric';
          exit;
       }
    }

    sort($paramsKeys);

    if($paramsKeys[0] != 0)
    {
       echo 'Parameters must start from 0';
       exit;
    }

    for($i = 1; $i < count($paramsKeys); $i++)
    {
       if($paramsKeys[$i] != $paramsKeys[$i - 1] + 1)
       {
          echo 'Expected a ' . ($paramsKeys[$i - 1] + 1) . ' parameter';
          exit;
       }
    }

    foreach($params as $key => $value)
    {
       if(empty($value) || $value == '')
       {
          echo 'Empty names are not allowed';
          exit;
       }
    }

    if(count(array_unique($params)) !== count($params))
    {
        echo 'Names must be unique';
        exit;
    }

    delete_table($conn, PLAYERS_TABLE);
    create_table($conn, PLAYERS_TABLE, [
        "internal_id" => "SERIAL PRIMARY KEY",
        "external_id" => "INT",
        "nome" => "VARCHAR(200)",
        "squadra" => "VARCHAR(100)",
        "ruolo" => "VARCHAR(20)",
        "qta" => "INT",
        "manager_id" => "INT",
        "payed" => "INT",
        'extracted' => "INT"
    ]);

    delete_table($conn, MANAGERS_TABLE);
    create_table($conn, MANAGERS_TABLE, [
        "internal_id" => "SERIAL PRIMARY KEY",
        "nome" => "VARCHAR(100)"
    ]);

    foreach($params as $key => $value)
    {
        pg_query($conn, "INSERT INTO " . MANAGERS_TABLE .
                        "(internal_id, nome) VALUES(DEFAULT, '$value')"
                );
    }


 }

 function extract_player($conn)
 {

   // Get previous player extraction position
   $result = pg_query($conn, "SELECT MAX(extracted) AS max FROM " . PLAYERS_TABLE);
   $indexMax = pg_fetch_array($result)['max'];

   // Get all unextracted records
   $result = pg_query($conn, "SELECT internal_id FROM " . PLAYERS_TABLE .
            " WHERE extracted IS NULL"
   );
   $ids = [];
   while ($row = pg_fetch_array($result)) {
      $ids[] = $row['internal_id'];
   }

   // Extract random number matching the position of an unextracted record
   $randId = rand(0, count($ids)-1);

   // Get the random record
   $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
            " WHERE internal_id=$ids[$randId]"
   );

   if($result)
   {
      // Save record data in response object
      $assocResult = pg_fetch_assoc($result);
      $response = [
         'status' => null,
         'data' => [
            'id' => $assocResult['internal_id'],
            'nome' => $assocResult['nome'],
            'squadra' => $assocResult['squadra'],
            'ruolo' => $assocResult['ruolo'],
            'prezzo_base' => $assocResult['qta'],
            'ordine_estrazione' => $assocResult['extracted']
         ] 
      ];
      // Update extracted record with new extraction position
      $result = pg_query($conn, "UPDATE " . PLAYERS_TABLE .
               " SET extracted=$indexMax+1" .
               " WHERE internal_id=$ids[$randId]"
      );

      // Update response object with success status and extracted record position
      if($result)
      {
         $response['status'] = 'success';
         $response['data']['ordine estrazione'] = $indexMax+1;
      } else
      {
         $response = [
            'status' => 'something went wrong'
         ];
      }
   } else
   {
      $response = [
         'status' => 'something went wrong'
      ];
   }

   echo json_encode($response);
   
   
 }

 function unextract_player($conn, $params)
 {
   // Verifica che sia presente il parametro identificativo del record
   if(checkParams($params, [
      'order_position'
   ]))
   {
    $orderPosition = $params['order_position'];
   }

   // Salva il record come non estratto
   $result = pg_query($conn, "UPDATE " . PLAYERS_TABLE .
               " SET extracted=NULL" .
               " WHERE extracted=$orderPosition"
            );

   if(!$result)
   {
      $response = [
            'status' => 'something went wrong'
      ];
      echo json_encode($response);
      exit;
   }  

   if(pg_affected_rows($result) < 1)
   {
      $response = [
         'status' => 'success',
         'error' => 'please, select an existent ordered position'
      ];
      echo json_encode($response);
      exit;
   }       
          
   // Se non è il primo record estratto         
   if($orderPosition > 1)
   {
      // Seleziona il record estratto in precedenza
      $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
            " WHERE extracted=$orderPosition-1"
      );
      if(!$result)
      {
         $response = [
            'status' => 'something went wrong'
         ];
         echo json_encode($response);
         exit;
      }
   } else
   {
      // Estrae un record a caso
      $result = pg_query($conn, "SELECT internal_id FROM " . PLAYERS_TABLE .
            " WHERE extracted IS NULL"
      );
      if(!$result)
      {
         $response = [
            'status' => 'something went wrong'
         ];
         echo json_encode($response);
         exit;
      }
      $ids = [];
      while ($row = pg_fetch_array($result)) {
         $ids[] = $row['internal_id'];
      }

      $randId = rand(0, count($ids)-1);

      $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
               " WHERE internal_id=$ids[$randId]"
      );
      if(!$result)
      {
         $response = [
            'status' => 'something went wrong'
         ];
         echo json_encode($response);
         exit;
      }
   }

   $assocResult = pg_fetch_assoc($result);
      $response = [
         'status' => 'success',
         'data' => [
            'id' => $assocResult['internal_id'],
            'nome' => $assocResult['nome'],
            'squadra' => $assocResult['squadra'],
            'ruolo' => $assocResult['ruolo'],
            'prezzo base' => $assocResult['qta'],
            'ordine estrazione' => $assocResult['extracted']
         ] 
      ];
   echo json_encode($response);   
 }

 function buy_player($conn, $params)
 {
    if(checkParams($params, [
        'internal_id',
        'manager',
        'payed'
    ]))
    {
      $internalId = $params['internal_id'];
      $manager = $params['manager'];
      $payed = $params['payed'];
    }

    $result = pg_query($conn, "UPDATE " . PLAYERS_TABLE .
                              " SET manager_id=$manager, payed=$payed
                                WHERE internal_id=$internalId"
            );
    
    if(!$result)
    {
      $response = [
           'status' => 'something went wrong'
      ];
      echo json_encode($response);
      exit;
    }  
         
    if(pg_affected_rows($result) < 1)
    {
      $response = [
            'status' => 'success',
            'error' => 'please, select an existent record'
      ];
      echo json_encode($response);
      exit;
    }

    $response = [
      'status' => 'success'
    ];
    echo json_encode($response);
     
 }

 function rollback_player($conn, $params)
 {
    if(checkParams($params, [
        'internal_id'
    ]))
    {
       $internalId = $params['internal_id'];
    }

    $result = pg_query($conn, "UPDATE " . PLAYERS_TABLE .
                              " SET manager_id=NULL, payed=NULL
                                WHERE internal_id=$internalId"
            );
    if(!$result)
    {
      $response = [
          'status' => 'something went wrong'
      ];
      echo json_encode($response);
      exit;
    }  
                 
    if(pg_affected_rows($result) < 1)
    {
      $response = [
         'status' => 'success',
         'error' => 'please, select an existent record'
      ];
      echo json_encode($response);
      exit;
    }
    
    $response = [
      'status' => 'success'
    ];
    echo json_encode($response);
 }

 function upload_data($conn, $params)
 {
    checkMethod('POST');
    $uploadedFile = fopen($_FILES['file']['tmp_name'], 'r') or die("Unable to open file!");

    // Ignore first line
    fgets($uploadedFile);

    while(!feof($uploadedFile)) {
      $currentLine = fgets($uploadedFile);
      $currentLine = explode(',', $currentLine);
      $externalId = $currentLine[0];
      print_r($currentLine);
      $nome = str_replace("'", "''", $currentLine[3]);
      $squadra = str_replace("'", "''", $currentLine[4]);
      $ruolo = str_replace(";", ",", $currentLine[2]);
      $qta = $currentLine[5];
      $query = pg_query($conn, "INSERT INTO " . PLAYERS_TABLE .
                              " VALUES(DEFAULT,
                              $externalId,
                              '$nome',
                              '$squadra',
                              '$ruolo',
                              $qta,
                              DEFAULT,
                              DEFAULT,
                              DEFAULT)"
            );
      echo $query;
      echo pg_last_error($conn);
      echo 'a<br>';
    }
      
    fclose($uploadedFile);
 }

?>