<?php

$key = "-- secure key --";
$dnsSourceFile = "/etc/pihole/custom.list";
$cnameSourceFile = "/etc/dnsmasq.d/05-pihole-custom-cname.conf";

if (!isset($_GET["key"]) || $_GET["key"] != $key)
	die();

if (!isset($_GET["name"]) || !isset($_GET["action"]))
	die();

$name = $_GET["name"];
$action = $_GET["action"];

$filesArr = [
    "dns" => $dnsSourceFile,
    "cname" => $cnameSourceFile,
];

if (!isset($filesArr[$name]) || !is_file($filesArr[$name]))
	die();

$fp = fopen($filesArr[$name], "r")
	or die();
	
if (!flock($fp, LOCK_SH))
	die();

if ($action == "hash")
{
	$ctx = hash_init("sha256");
	while (!feof($fp))
	{
		set_time_limit(0);
		$buffer = fgets($fp, 8*1024);
		hash_update($ctx, $buffer);
	}
	
	header("Content-type: text/plain");
	print("ok:" . hash_final($ctx, false));
}
else if ($action == "download")
{
	header('Cache-control: private');
	header('Content-Type: plain/text');
	header('Content-Length: ' . filesize($filesArr[$name]));
	header('Content-Disposition: filename=' . basename($filesArr[$name]));
	
	while (!feof($fp))
	{
		set_time_limit(0);
		print(fread($fp, 8*1024));
		flush();
		ob_flush();
	}		
}
	
fclose($fp);
?>
