<?php
session_start();

if (!isset($_GET['id']) || isset($_GET['id']) && $_GET['id'] != $_SESSION['id'])
{
	$_SESSION['id'] = '';
	die("Error");
}

if (file_exists(dirname(__FILE__)."/config.php"))
{
    require_once dirname(__FILE__).'/config.php';
}
if (!$servers)
{
	die("No config found.");
}

$server = null;
if (isset($_GET['s']) && intval($_GET['s']) < count($servers)) {
	$server = intval($_GET['s']);
}
if (isset($_GET['db'])) {
	$db = intval($_GET['db']);
}
if (isset($_GET['async'])) {
	$async = intval($_GET['async']);
}

$FLUSHDB = 'FLUSHDB';
if (isset($command['FLUSHDB']) && !is_null($command['FLUSHDB']) && !empty($command['FLUSHDB']))
{
	$FLUSHDB = $command['FLUSHDB'];
}
$FLUSHALL = 'FLUSHALL';
if (isset($command['FLUSHALL']) && !is_null($command['FLUSHALL']) && !empty($command['FLUSHALL']))
{
	$FLUSHALL = $command['FLUSHALL'];
}

$error = null;

$fp = @fsockopen($servers[$server][1], $servers[$server][2], $errno, $errstr, 30);

$info = array();

if (!$fp) {
	die($errstr);
} else {
	$command = '';
	$ASYNC   = '';

	if (isset($servers[$server][3]) && !is_null($servers[$server][3]) && !empty($servers[$server][3]))
	{
		$pwd = $servers[$server][3];
		$command = "AUTH $pwd\r\n";
	}
	if ($async) // we want async flush
	{
		$ASYNC = ' ASYNC';
	}
	if ($db != -1) // one specific database
	{
		$command .= "SELECT $db\r\n$FLUSHDB$ASYNC\r\nQUIT\r\n";
	}
	else // entire instance
	{
		$command .= "$FLUSHALL$ASYNC\r\nQUIT\r\n";
	}

	fwrite($fp, $command);
	while (!feof($fp)) {
		$info[] = trim(fgets($fp));
	}
	fclose($fp);
}

if (array_unique($info) === array('+OK'))
{
	$_SESSION['id'] = '';
	echo "Success";
}
elseif (DEBUG)
{
	echo $command.PHP_EOL;
	echo implode("\n", $info);
}
