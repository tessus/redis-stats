<?php
session_start();

$localVersion = $_SESSION['localVersion'];
$updateURL    = $_SESSION['updateURL'];

$remoteVersion = trim(@file_get_contents($updateURL));

if (empty($remoteVersion))
{
	// Something went wrong
	die('Error');
}

if (version_compare($remoteVersion, $localVersion, '>'))
{
	// Update available: return new version
	echo $remoteVersion;
}
else
{
	// No update available: return 0
	echo '0';
}
