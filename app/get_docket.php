<?php
//Script to fetch base copy of the docket master

$url = "http://www.opcso.org/dcktmstr/666666.php?&docase=" . $argv[2];
$ch = curl_init($url);
$fp = fopen($argv[4] . '/' . $argv[3] . '.dk', "w");
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_exec($ch);
curl_close($ch);
fclose($fp);
