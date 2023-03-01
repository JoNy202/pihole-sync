<?php

$masterServer = "{master-pihole-ip}";
$key = "{secret-key}";
$dnsSyncFile = "/etc/pihole/custom.sync.list";
$cnameSyncFile = "/etc/dnsmasq.d/06-pihole-sync-custom-cname.conf";
$dnsmasqConfigFile = "/etc/dnsmasq.d/02-pihole-sync.conf";
$piholeBin = "/usr/local/bin/pihole";

$dnsmasqConfig = "#updated automatically" . PHP_EOL . "addn-hosts={$dnsSyncFile}";
if (!is_file($dnsmasqConfigFile))
{
	print("info: creating dnsmasq config '{$dnsmasqConfigFile}'" . PHP_EOL);
	file_put_contents($dnsmasqConfigFile, $dnsmasqConfig)
		or die("error: cannot create dnsmasq config '{$dnsmasqConfigFile}'");
}
else
{
	$dnsmasqConfigLocal = file_get_contents($dnsmasqConfigFile)
		or die("error: cannot read dnsmasq config '{$dnsmasqConfigFile}'" . PHP_EOL);
		
	if ($dnsmasqConfigLocal != $dnsmasqConfig)
	{
		print("info: updating dnsmasq config '{$dnsmasqConfigFile}'" . PHP_EOL);
		file_put_contents($dnsmasqConfigFile, $dnsmasqConfig)
			or die("error: cannot update dnsmasq config '{$dnsmasqConfigFile}'" . PHP_EOL);
	}
}

$requestUrl = "http://{$masterServer}/sync/{$key}";
$isNeedDNSReload = false;
$isNeedDNSRestart = false;

$filesArr = [
	"dns" => $dnsSyncFile,
	"cname" => $cnameSyncFile,
];

foreach ($filesArr as $name => $file)
{	
	if (($fp = fopen($file, "cb+")) === false)
	{
		print("error: file open error '{$file}'" . PHP_EOL);
		continue;
	}
		
	if (!flock($fp, LOCK_EX))
	{
		print("error: file lock error '{$file}'" . PHP_EOL);
		continue;
	}
	
	$hash = file_get_contents("{$requestUrl}/{$name}/hash")
		or die("error: could not connect to server {$masterServer}" . PHP_EOL);
		
	$hash = str_replace("ok:", "", $hash, $count);
	if ($count != 1)
	{
		print("error: hash request '$name' returned incorrect response" . PHP_EOL);
		continue;
	}

	$ctx = hash_init("sha256");
	while (!feof($fp))
	{
		set_time_limit(0);
		$buffer = fgets($fp, 8*1024);
		hash_update($ctx, $buffer);
	}

	if (hash_final($ctx, false) == $hash)
	{
		print("info: sync '$name' is not required" . PHP_EOL);
		continue;
	}

	rewind($fp);
	ftruncate($fp, 0);
	
	$st = fopen("{$requestUrl}/{$name}/download", 'rb')
		or die("error: could not connect to server {$masterServer}" . PHP_EOL);
		
	while (!feof($st))
	{
		set_time_limit(0);
		$data = stream_get_contents($st, 8*1024);
		fwrite($fp, $data);
		fflush($fp);
	}
	
	fclose($st);
	fclose($fp);
	
	if ($name == "dns")
		$isNeedDNSReload = true;
	
	if ($name == "cname")
		$isNeedDNSRestart = true;
	
	print("info: file '{$name}' is synced" . PHP_EOL);
}

$piholeExecute = NULL;
if ($isNeedDNSRestart)
{
	print("info: restarting DNS" . PHP_EOL);
	$piholeExecute = $piholeBin . " restartdns";
}
else if ($isNeedDNSReload)
{
	print("info: reloading DNS" . PHP_EOL);
	$piholeExecute = $piholeBin . " restartdns reload";
}

if ($piholeExecute !== NULL)
{
	$output = null;
	$status = -1;
	// for cron
	putenv("PATH=" . getenv('PATH') . ":/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin");
	exec($piholeExecute, $output, $status);
	if ($status !== 0)
		print("error: restart/reload failed." . PHP_EOL);
}

?>