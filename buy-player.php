<?php
 echo 'ciao';
 $dbconn = pg_connect("host=ec2-54-228-125-183.eu-west-1.compute.amazonaws.com
                       dbname=d6memn437ahh1b
                       user=uqkswzeelyfzxo
                       password=13e44cbf86a3cd6b19c584aee01cf90c97493aadf366fc54e1f67eebb6e13a15")
    or die('Could not connect: ' . pg_last_error());
 print_r($dbconn);   
?>