<?php

$test = "Ping: 23.344 ms";

$results = str_replace("Ping: ", "" , $test);
$result = explode(' ' , $results);
echo $result[0];
echo $result[1];







?>

