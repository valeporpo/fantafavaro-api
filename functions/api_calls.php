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

 function extract_player($conn, $params)
 {

   if(checkParams($params, [
      'order_position'
   ]))
   {
    $orderPosition = $params['order_position'];
   } else 
   {
     $response = [
        'status' => 'something went wrong',
        'error' => 'some required params are missing'
     ];
     echo json_encode($response);
     exit;
   }

   $result = pg_query($conn, "SELECT MAX(extracted) AS max, COUNT(*) AS count
                              FROM " . PLAYERS_TABLE);

   if(!$result)
   {
      $response = [
         'status' => 'something went wrong',
         'error' => 'server error'
      ];
      echo json_encode($response);
      exit;
   }                           
   $resultArray = pg_fetch_array($result); 
   $indexMax = $resultArray['max'];
   $numPlayers = $resultArray['count'];
   // Se viene richiesto un nuovo giocatore 
   if($orderPosition == 0 && empty($indexMax) || $orderPosition == $indexMax)
   {
      
      // Get all unextracted records
      $result = pg_query($conn, "SELECT internal_id FROM " . PLAYERS_TABLE .
                                " WHERE extracted IS NULL"
      );
      $ids = [];
      while($row = pg_fetch_array($result))
      {
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
         if(intval($assocResult['extracted']) < $numPlayers) {
            $position = 'middle last extracted';
         } else {
            $position = 'last';
         }
         $response = [
               'status' => null,
               'position' => $position,
               'data' => [
                     'id' => intval($assocResult['internal_id']),
                     'nome' => $assocResult['nome'],
                     'squadra' => $assocResult['squadra'],
                     'ruolo' => $assocResult['ruolo'],
                     'prezzo_base' => intval($assocResult['qta']),
                     'ordine_estrazione' => intval($assocResult['extracted']),
                     'buyed' => isset($assocResult['payed']) ? true : false
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
            $response['data']['ordine_estrazione'] = $indexMax+1;
         } else
         {
            $response = [
               'status' => 'something went wrong',
               'error' => 'server error'
            ];
         }
      } else
      {
         $response = [
             'status' => 'something went wrong',
             'error' => 'server error'
         ];
      }
                  
      echo json_encode($response);
      exit;

   } else
   {
      $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
                             " WHERE extracted=$orderPosition+1"
                     );

      if($result)
      {
         $assocResult = pg_fetch_assoc($result);
         if(intval($assocResult['extracted']) < $numPlayers)
         {
            if($indexMax == intval($assocResult['extracted']))
            {
               $position = 'middle last extracted';
            } else
            {
               $position = 'middle not last extracted';
            }
         } else
         {
            $position = 'last';
         }
         $response = [
             'status' => 'success',
             'position' => $position,
             'data' => [
                  'id' => intval($assocResult['internal_id']),
                  'nome' => $assocResult['nome'],
                  'squadra' => $assocResult['squadra'],
                  'ruolo' => $assocResult['ruolo'],
                  'prezzo_base' => intval($assocResult['qta']),
                  'ordine_estrazione' => intval($assocResult['extracted']),
                  'buyed' => isset($assocResult['payed']) ? true : false
             ] 
         ]; 
      } else
      {
         $response = [
            'status' => 'something went wrong'
         ];
      }
      
      echo json_encode($response);
      exit;
                    
   }
 }

 function unextract_player($conn, $params)
 {

   /*
    Chiamata dal 1°giocatore estratto: begin
    In tutti gli altri casi: middle not last extracted

   */
   // Verifica che sia presente il parametro identificativo del record
   if(checkParams($params, [
      'order_position'
   ]))
   {
    $orderPosition = $params['order_position'];
   } else 
   {
     $response = [
        'status' => 'something went wrong',
        'error' => 'some required params are missing'
     ];
     echo json_encode($response);
     exit;
   }

   $result = pg_query($conn, "SELECT MAX(extracted) AS max, COUNT(*) AS count
                              FROM " . PLAYERS_TABLE .
                              " WHERE extracted IS NOT NULL");
   
   if(!$result)
   {
      $response = [
         'status' => 'something went wrong',
         'error' => 'server error'
      ];
      echo json_encode($response);
      exit;
   }

   $resultArray = pg_fetch_array($result);    
   $indexMax = $resultArray['max'];
   // Se l'indice è superiore al numero di giocatori estratti
   if($orderPosition > $indexMax)
   {
      $response = [
         'status' => 'something went wrong',
         'error' => 'invalid index provided'
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
            'status' => 'something went wrong',
            'error' => 'server error'
         ];
         echo json_encode($response);
         exit;
      }
      $assocResult = pg_fetch_assoc($result);
      $response = [
         'status' => 'success',
         'position' => 'middle not last extracted',
         'data' => [
            'id' => intval($assocResult['internal_id']),
            'nome' => $assocResult['nome'],
            'squadra' => $assocResult['squadra'],
            'ruolo' => $assocResult['ruolo'],
            'prezzo_base' => intval($assocResult['qta']),
            'ordine_estrazione' => intval($assocResult['extracted']),
            'buyed' => isset($assocResult['payed']) ? true : false
         ] 
      ];
      echo json_encode($response);
      exit;
   } else
   {
      $response = [
         'status' => 'success',
         'position' => 'begin'
      ];
      echo json_encode($response);
      exit;
   }
 }

 function retrieve_player($conn) {
   $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE);

   // Errore del server
   if(!$result)
   {
      $response = [
         'status' => 'something went wrong'
      ];
      echo json_encode($response);
      exit;
   // Nessun giocatore presente 
   } else if(pg_affected_rows($result) == 0)
   {
      $response = [
         'status' => 'something went wrong',
         'error' => 'game not initialized'
      ];
      echo json_encode($response);
      exit;
   } else
   {      
      $maxIndex = 0;
      $lastExtracted = [];
      while($row = pg_fetch_array($result)) {
         if($row['extracted'] > $maxIndex)
         {
            $maxIndex = $row['extracted'];
            $lastExtracted = $row;
         }
      }

      // Nessun giocatore estratto
      if(empty($lastExtracted))
      {
         $response = [
            'status' => 'success',
            'position' => 'begin'
         ];
         echo json_encode($response);
         exit;
      }

      // Almeno un giocatore estratto
      $response = [
         'status' => 'success',
         'position' => 'middle last extracted'
      ];
      $response['data'] = [
         'id' => intval($lastExtracted['internal_id']),
         'nome' => $lastExtracted['nome'],
         'squadra' => $lastExtracted['squadra'],
         'ruolo' => $lastExtracted['ruolo'],
         'prezzo_base' => intval($lastExtracted['qta']),
         'ordine_estrazione' => intval($lastExtracted['extracted']),
         'buyed' => isset($lastExtracted['payed']) ? true : false
      ];

      if($maxIndex == pg_num_rows($result)) { $response['position'] = 'last'; }
      echo json_encode($response);
      exit;
   }
 }

 function retrieve_managers($conn)
 {
   $result = pg_query($conn, "SELECT managers.internal_id AS id,
                                     managers.nome AS nome,
                                     (SELECT COUNT(payed) FROM players WHERE managers.internal_id=players.manager_id) AS giocatori,
                                     CASE
                                        WHEN (SELECT COUNT(payed) FROM players WHERE managers.internal_id=players.manager_id) > 0
                                        THEN (SELECT SUM(payed) FROM players WHERE managers.internal_id=players.manager_id)
                                        ELSE 0
                                     END AS spesa
                              FROM managers");
   if(!$result)
   {
      $response = [
         'status' => 'something went wrong',
         'error' => 'server error'
      ];
      echo json_encode($response);
      exit;
   } else
   {
      $id = 0;
      $response = [
         'status' => 'success',
         'data' => []
      ];
      while($row = pg_fetch_array($result)) {
         $response['data'][$id] = [
            'id' => intval($row['id']),
            'nome' => $row['nome'],
            'giocatori' => intval($row['giocatori']),
            'spesa' => intval($row['spesa'])
         ];
         $id++;
      }
      echo json_encode($response);
      exit;
   }
 }

 function buy_player($conn, $params)
 {
    if(checkParams($params, [
        'internal_id',
        'manager_id',
        'payed'
    ]))
    {
      $internalId = $params['internal_id'];
      $manager = $params['manager_id'];
      $payed = $params['payed'];
    } else 
    {
      $response = [
         'status' => 'something went wrong',
         'error' => 'some required params are missing'
      ];
      echo json_encode($response);
      exit;
    }

    $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
                              " WHERE internal_id=$internalId"
    );

    if(!$result)
    {
      $response = [
         'status' => 'something went wrong',
         'error' => 'server error'
      ];
      echo json_encode($response);
      exit;
    }

    if(pg_num_rows($result) != 1)
    {
      $response = [
         'status' => 'something went wrong',
         'error' => 'please, seleact an existent record'
      ];
      echo json_encode($response);
      exit;
    }

    $extracted = pg_fetch_array($result)['extracted'];

    $result = pg_query($conn, "UPDATE " . PLAYERS_TABLE .
                              " SET manager_id=$manager, payed=$payed
                                WHERE internal_id=$internalId"
            );
    
    if(!$result)
    {
      $response = [
           'status' => 'something went wrong',
           'error' => 'server error'
      ];
      echo json_encode($response);
      exit;
    }
    
    $response = [
      'status' => 'success',
      'data' => [
          'internal_id' => intval($internalId),
          'extracted'=> intval($extracted)
      ]
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

 function get_players($conn, $params)
 {
    if(checkParams($params, [
                              'mode'
                            ]
                  )
      )
      {
         $mode = $params['mode'];
         if($mode != "available" && $mode != "buyed" && $mode != "all")
         {
            $response = [
               'status' => 'something went wrong',
               'error' => 'invalid param value'
            ];
            echo json_encode($response);
            exit;
         }
      } else 
      {
         $response = [
            'status' => 'something went wrong',
            'error' => 'some required params are missing'
         ];
         echo json_encode($response);
         exit;
      }

      $condition = $mode == "available" ? "payed IS NULL" :
                   ($mode == "buyed" ? "payed IS NOT NULL" : "1=1");
      $result = pg_query($conn, "SELECT * FROM " . PLAYERS_TABLE .
                                 " WHERE $condition"
      );

      if(!$result)
      {
         $response = [
            'status' => 'something went wrong'
         ];
         echo json_encode($response);
         exit;
      } else
      {
         $data = [];
         $i = 0;
         while($row = pg_fetch_array($result)) {
            $data[$i] = [
               'id' => intval($row['internal_id']),
               'nome' => $row['nome'],
               'squadra' => $row['squadra'],
               'ruolo' => $row['ruolo'],
               'prezzo_base' => intval($row['qta'])
            ];
            if($mode === 'buyed')
            {
               $data[$i]['manager'] = intval($row['manager_id']);
               $data[$i]['payed'] = intval($row['payed']);
            } 
            $i++;
         }
         $response = [
            'status' => 'success',
            'data' => $data
         ];
         echo json_encode($response);
         exit;
      }
 }

 function refresh_players($conn)
 {
   
   $result = pg_query($conn, "UPDATE players
                              SET extracted = NULL, manager_id = NULL, payed = NULL
                              WHERE 1=1");
   if($result)
   {
      echo 'success';
   } else
   {
      echo 'fail';
   }
 }

?>