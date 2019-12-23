<?php

session_start();

$id = bin2hex(random_bytes(16));
$_SESSION['id'] = $id;

// Default config
$servers = [
	[ 'Local', '127.0.0.1', 6379 ],
];

if (file_exists(dirname(__FILE__)."/config.php"))
{
	require_once dirname(__FILE__).'/config.php';
}
if (!$servers)
{
	die("No servers in config found.");
}

define("URL", "https://github.com/tessus/redis-stats");
define("UPDATE_URL", "https://raw.githubusercontent.com/tessus/redis-stats/master/VERSION");

$_SESSION['updateURL'] = UPDATE_URL;

// Default settings
if (!defined('DEBUG'))
{
	define("DEBUG", false);
}
if (!defined('FLUSHDB'))
{
	define("FLUSHDB", true);
}
if (!defined('FLUSHALL'))
{
	define("FLUSHALL", true);
}
if (!defined('CONFIRM_FLUSHDB'))
{
	define("CONFIRM_FLUSHDB", true);
}
if (!defined('CONFIRM_FLUSHALL'))
{
	define("CONFIRM_FLUSHALL", true);
}
if (!defined('CHECK_FOR_UPDATE'))
{
	define("CHECK_FOR_UPDATE", true);
}
if (!defined('STATUS_LINE'))
{
	define("STATUS_LINE", "bottom");
}

// Get local version
$localVersion = trim(@file_get_contents('./VERSION'));
$_SESSION['localVersion'] = $localVersion;

// Process GET request
$server = 0;
if (isset($_GET['s']) && intval($_GET['s']) < count($servers))
{
	$server = intval($_GET['s']);
}
$serverName = $servers[$server][0];

// Command mapping
$AUTH = 'AUTH';
if (isset($command[$serverName]['AUTH']) && !is_null($command[$serverName]['AUTH']) && !empty($command[$serverName]['AUTH']))
{
	$AUTH = $command[$serverName]['AUTH'];
}
$INFO = 'INFO';
if (isset($command[$serverName]['INFO']) && !is_null($command[$serverName]['INFO']) && !empty($command[$serverName]['INFO']))
{
	$INFO = $command[$serverName]['INFO'];
}

// Talk to Redis server
$error = null;

$fp = @fsockopen($servers[$server][1], $servers[$server][2], $errno, $errstr, 30);

$data    = [];
$section = '';
$details = [];

if (!$fp)
{
	$error = $errstr;
}
else
{
	$redisCommand = '';

	isset($servers[$server][3]) ? $pwdEntry = $servers[$server][3] : $pwdEntry = null;
	if (!is_null($pwdEntry) && !empty($pwdEntry))
	{
		if (is_array($pwdEntry))
		{
			if (!isset($pwdEntry[1]) || is_null($pwdEntry[1]) || empty($pwdEntry[1]))
			{
				$pwdEntry[1] = '0';
			}
			$credentials = "$pwdEntry[0] $pwdEntry[1]";
		}
		else
		{
			$credentials = $pwdEntry;
		}
		$redisCommand = "$AUTH $credentials\r\n";
	}
	$redisCommand .= "$INFO\r\nQUIT\r\n";

	fwrite($fp, $redisCommand);
	while (!feof($fp))
	{
		$info = explode(':', trim(fgets($fp)), 2);
		if (isset($info[0]) && substr($info[0], 0, 2) === '# ')
		{
			$section = substr($info[0], 2);
		}
		if (isset($info[1]))
		{
			$data[$info[0]]              = $info[1];
			$details[$section][$info[0]] = $info[1];
		}
	}
	$section = '';
	fclose($fp);
}

if (!$data && !$error)
{
	$error = "No data is available.<br>Maybe a password is required to access the database or the password is wrong.";
}

$err = '-ERR unknown command';
if (is_array($data) && !empty($data) && substr(array_keys($data)[0], 0, strlen($err)) === $err)
{
	$error = "Command AUTH or INFO has been renamed on the server.";
}

debug($data);
debug($details);

// get a list of active databases
$getDbIndex = function($db)
{
	return (int) substr($db, 2);
};
$redisDatabases = array_values(array_map($getDbIndex, preg_grep("/^db[0-9]+$/", array_keys($data))));

function time_elapsed($secs)
{
	if (!$secs) return;
	$bit = [
		' year'      => $secs / 31556926 % 12,
		' week'      => $secs / 604800 % 52,
		' day'       => $secs / 86400 % 7,
		' hour'      => $secs / 3600 % 24,
		' minute'    => $secs / 60 % 60,
		' second'    => $secs % 60,
	];

	foreach ($bit as $k => $v)
	{
		if ($v > 1)  $ret[] = $v . $k . 's';
		if ($v == 1) $ret[] = $v . $k;
	}
	if (count($ret) > 1)
	{
		array_splice($ret, count($ret) - 1, 0, 'and');
	}

	return implode(' ', $ret);
}

function debug($var, $pre = true)
{
	if (DEBUG)
	{
		if ($pre) echo "<pre>".PHP_EOL;
		var_dump($var);
		if ($pre) echo "</pre>".PHP_EOL;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Redis Stats</title>
<style>
html {
	font-size: 16px;
	line-height: 1.4;
}
* {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
body {
	font-family: "Source Sans Pro",Helvetica,Arial,sans-serif;
	color: #333;
	margin:0;
}

h1 {
	background: #8892BF;
	border-bottom: 4px solid #4F5B93;
	padding: 0.25em;
	margin-top: 0;
	color: #22242F;
	text-align: center;
}

button {
	margin-top: 2px;
	margin-bottom: 2px;
	vertical-align: middle !important;
	line-height: normal;
	padding: 2px 4px 2px 4px !important;
}

form {
	margin: 1em 0;
}

.wrapper {
	text-align: center;
}

.grid {
	font-size: 0;
}

.box {
	font-size: 1rem;
	vertical-align: top;
	background-color: #E6E6E6;
	text-align: center;
	margin: 6px;
	display: inline-block;
	border: 1px solid #ccc;
	border-top: 0;
}

.menu1 {
	font-size: 1rem;
	width: 350px;
	text-align: right;
	display: inline-block;
	padding-right: 50px;
}

.menu2 {
	font-size: 1rem;
	width: 350px;
	text-align: left;
	display: inline-block;
	padding-left: 20px;
}

.col {
	width: 224px;
	display: inline-block;
}

.col2 {
	width: 460px;
	display: inline-block;
}

.col3 {
	width: 700px;
	display: inline-block;
}

.boxmsg {
	font-size: 1rem;
	vertical-align: middle;
	text-align: center;
	margin: 6px;
	display: inline-block;
	border: 1px solid #ccc;
	padding: 2px 2px 2px 2px !important;
}

.boxmsg a {
	text-decoration: none;
	color: #0000FF;
}

.boxmsg a:hover {
	text-decoration: underline wavy;
	color: #0000FF;
}

.col2 .col {
	vertical-align: middle;
}

.col > * {
	padding: 0 4px 4px;
}

.box h2 {
	margin: 0 0 0.5em;
	padding: 0.25em;
	font-size: 1.15em;
	background: #E2E4EF;
	border-top: 2px solid #4F5B93;
	border-bottom: 1px solid #C4C9DF;
}

.box h3 {
	margin: 0.5em 0 0.5em;
	padding: 2px 0 2px 0;
	font-size: 1.05em;
	background: #E2E4EF;
	border-top: 1px solid #C4C9DF;
	border-bottom: 1px solid #C4C9DF;
}

.key {
	font-weight: bold;
	font-size: 42px;
}

.details { margin: 1em 0; }

.detail {
	text-align: left;
	font-size: 0;
}

.detail span {
	font-size: 1rem;
	width: 50%;
	padding: 0 4px;
	display: inline-block;
}

.detail .title {
	text-align: right;
}

.detail .key {
	font-weight: bold;
	text-align: right;
}

#hitrate { position: relative; }

#hitrate .key {
	position: absolute;
	left: 25%;
	top: 50%;
	text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
	margin-top: -0.5em;
	padding: 0;
	z-index: 1;
	line-height: 1;
}

footer {
	padding: 1em 1em;
}
footer a {
	padding: 2.2em;
	text-decoration: none;
	opacity: 0.7;
	color: #22242F;
}
footer a:hover {
	opacity: 1;
}
footer > a {
	background-position: 5px 50%;
	background-repeat: no-repeat;
	background-color: transparent;
}
footer > a {
	background-position: 0 50%;
	background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAcCAYAAAB75n/uAAAABmJLR0QA/wD/AP+gvaeTAAAByUlEQVRIid3VsWsUURDH8Y9rDJyVSeedUSGlVVJYi1YiRghqJSj2wTR2Fv4F2qoIglrZiUkqBSOC/4ClaEAhxRnRoI1nchb7lixzu7ncXaUDU8yb2e/vvXnLPP5129cnP4ULOIfjKYbPWMMSnuPLoMIt3EcH3T6+hWc4tlf4HDb3AI6+ifP94AtpR4PCy6dZqIOfxZ8R4GWRuQhvhbbcQQPX5ZfZxTreJ19Pa2uppoG7oV3NssDDsIurpVwjFidrplxh1wLjQZGYqmjNYgWwny0GRgdHMvl/vj8UfxxC4FOIxxLbSlB+OQS8sFeBtZxhOhQtjSCwHOLpDIfD4sYIAu0QNzO9/Z8YQWAyxFmGr2FxZgSB2RC3yftWvpgNHBoCPoFvgfUiw+tQOIl7yAaAZ+mb2N5V8jFRjOV3dsbAG5zSe0dlG8NpvA077+K30gR4mhZX5H1cLRX+xM0K+C38qgAX/rhc3MSPlHiEk/hgZzqeqBCY3QX+XcX8uoRt+VyawVGcqYHDwRr4Fi7WfONGEmnjNi7jiuoLP1ADr31wyicp2lX4eEXduN621O48WlN+8Z10ojqBbfnf8kT1m9HXWpjfJT+fav5j+wtaock1qj7sygAAAABJRU5ErkJggg==');
	font-size: 90%;
}
</style>
<script>
(function() {
	var ce = function(name) {
		return document.createElement(name);
	},
	DIV = "div",
	PX = "px";

function setStyle(elem, key, value) {
	var style = elem.style,
		styles, i;

	if (typeof key !== "object") {
		styles = {};
		styles[key] = value;
	} else {
		styles = key;
	}

	for (i in styles) {
		style[i] = styles[i];
	}

	return elem;
}

function createSlice(sizeNum) {
	var newSlice = ce(DIV),
		pieSize = sizeNum + PX;

	setStyle(newSlice, {
		"position": "absolute",
		"top": 0,
		"left": 0,
		"width": pieSize,
		"height": pieSize,
		"webkitBorderRadius": pieSize,
		"mozBorderRadius": pieSize,
		"borderRadius": pieSize,
		"clip": ["rect(0,", pieSize, ",", pieSize, ",", sizeNum / 2, PX].join("")
	});

	return newSlice;
}

function createPieSlice(sizeNum, color) {
	var pie = ce(DIV),
		pieSize = sizeNum + PX;

	setStyle(pie, {
		"backgroundColor": color,
		"position": "absolute",
		"top": 0,
		"left": 0,
		"width": pieSize,
		"height": pieSize,
		"webkitBorderRadius": pieSize,
		"mozBorderRadius": pieSize,
		"borderRadius": pieSize,
		"clip": ["rect(0,", sizeNum / 2, PX, ",", sizeNum, PX, ",", 0].join("")
	});

	return pie;
}

function createPie(pieSize, pieces, baseColor, pieName){
	var sizeNum = parseFloat(pieSize.replace(PX, "")),
		numberOfSlices = pieces.length,

		pieContainer = ce(DIV),
		pieBackground = ce(DIV),
		newSlice,
		pie,

		sliceOffset,
		piePercentage,
		pieOffset,
		beforeDegree = 0,
		degree = 0,
		degreeOffset,
		i = 0,

		transform1,
		transform2;

	if (isNaN(sizeNum) || !numberOfSlices) {
		return;
	}

	//Pie Container
	if (pieName) {
		pieContainer.id=pieName;
	}
	setStyle(pieContainer, "display", "inline-block");

	//Pie Background
	setStyle(pieBackground, {
		"width": pieSize,
		"height": pieSize,
		"position": "relative",
		"webkitBorderRadius": pieSize,
		"mozBorderRadius": pieSize,
		"borderRadius": pieSize
	});
	if (baseColor) {
		setStyle(pieBackground, "backgroundColor", baseColor);
	}

	//Append Background to Container
	pieContainer.appendChild(pieBackground);

	//Loop through Slices
	for(; i < numberOfSlices; i++){
		//New Slice
		newSlice = createSlice(sizeNum);

		//New Slice Pie
		pie = createPieSlice(sizeNum, pieces[i]["color"]);

		//Get Percentage
		piePercentage = pieces[i]['value'];

		//Check if Percentage > 50
		if (piePercentage > 50) {
			sliceOffset = 180;
			pieOffset = 50
			degreeOffset = 0;

			transform1 = "rotate(" + sliceOffset + "deg)";
			setStyle(pie, {
				"webkitTransform": transform1,
				"mozTransform": transform1,
				"transform": transform1
			});

			transform2 = "rotate(" + beforeDegree + "deg)";
			setStyle(newSlice, {
				"clip": ["rect(0,", sizeNum, PX, "," , sizeNum, PX, "," , (sizeNum - 100) / 2, PX].join(""),
				"webkitTransform": transform2,
				"mozTransform": transform2,
				"transform": transform2
			});

			newSlice.appendChild(pie);
			pieBackground.appendChild(newSlice);

			newSlice = createSlice(sizeNum);
			pie = createPieSlice(sizeNum, pieces[i]["color"]);

			//If it's not first slice, then ...
			if (i != 0) {
				degreeOffset = 1;
			}
		} else {
			sliceOffset = pieOffset = degreeOffset = 0;
		}

		degree = parseFloat((piePercentage - pieOffset) * 180 / 50);

		transform1 = "rotate(" + (degree + degreeOffset) + "deg)";
		setStyle(pie, {
			"webkitTransform": transform1,
			"mozTransform": transform1,
			"transform": transform1
		});
		newSlice.appendChild(pie);

		if (sliceOffset || beforeDegree) {
			transform2 = "rotate(" + (beforeDegree + sliceOffset - degreeOffset) + "deg)";
			setStyle(newSlice, {
				"webkitTransform": transform2,
				"mozTransform": transform2,
				"transform": transform2
			});
		}

		pieBackground.appendChild(newSlice);

		beforeDegree += degree + sliceOffset;
	}

	return pieContainer;
}

window.createPie = createPie;
}());
</script>

</head>
<body onload="initRedisInfo()">
<div class="wrapper">   <!-- Wrapper  -->
<?php if (CHECK_FOR_UPDATE === true) { ?>
<button id="checkbutton" style="float: right; margin: 20px 10px 20px -200px;" onclick="checkForUpdate();">Check for update</button>
<?php } ?>
<h1>Redis Stats <span style="font-size: 50%;"><?php echo $localVersion; ?></span></h1>
<form method="get">
<label for="server">Server:</label>
<select onchange="this.form.submit()" id="server" name="s">
<?php foreach ($servers as $i => $serv): ?>
<option value="<?php echo $i ?>"<?php echo ($server == $i) ? ' selected="selected"' : '' ?>><?php echo $serv[0] ?></option>
<?php endforeach; ?>
</select>
</form>

<?php
if ($error)
{
	die($error . "\n</div>\n<footer><a href=\"" . URL . "\" target=\"_blank\">" . URL. "</a></footer>\n</body>\n</html>\n");
}
?>

<div class="grid">   <!-- Top Menu  -->
	<div class="menu1">
		Refresh Rate: <input type="text" id="rate" value="2" size="5" onkeyup="myInputTest()"> <button id="play" style="width: 55px;" onclick="playpause();">Play</button>
	</div>

	<div class="menu2">
		<button id="togglebutton" onclick="toggleDetails();">Toggle details</button>
		<button id="refreshbutton" onclick="location.reload();">Refresh</button>

		<?php
		if (FLUSHDB === true || FLUSHALL === true)
		{
			echo '<input type="checkbox" id="checkboxasync" onclick="toggleAsync();"> flush async';
		}
		?>
	</div>
</div>   <!-- Top Menu  -->

<?php if (STATUS_LINE === 'top') { ?>
<div id="msg" class='boxmsg col3' style="visibility: hidden;">
&nbsp;
</div>
<?php } ?>

<div class="grid">
<div class='box col2'>
	<h2>Hits</h2>
	<div class="grid">
	<div id="hitrate" class="col">
	<?php
		$hitRate = 0;
		if (($data['keyspace_hits'] + $data['keyspace_misses']) != 0)
		{
			$hitRate = sprintf('%.1f' , $data['keyspace_hits'] / ($data['keyspace_hits'] + $data['keyspace_misses']) * 100);
		}
	?>
	<div class="key"><?php echo ($hitRate) ? $hitRate."%" : '' ?></div>
	</div>
	<div class="col">
	<div class="detail">
		<span class="title">Hits:</span>
		<span><?php echo number_format($data['keyspace_hits']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Misses:</span>
		<span><?php echo number_format($data['keyspace_misses']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Expired:</span>
		<span><?php echo number_format($data['expired_keys']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Evicted:</span>
		<span><?php echo number_format($data['evicted_keys']) ?></span>
	</div>
	</div>
	</div>
	<?php if ($data['maxmemory'] > 0) echo "<br>\n"; ?>
</div>

<div class='box col'>
	<h2>Used Memory</h2>

	<div class="key"><?php echo $data['used_memory_human'] ?></div>
	<?php if ($data['maxmemory'] > 0) echo "/ ${data['maxmemory_human']}\n"; ?>
	<h2>Peak Memory</h2>
	<div class="key"><?php echo $data['used_memory_peak_human'] ?></div>
</div>

</div>
<div class="grid">

<div class='box col'>
	<h2>Uptime</h2>
	<p class="details"><?php echo time_elapsed($data['uptime_in_seconds']) ?></p>
</div>

<div class='box col'>
	<h2>Connection</h2>
	<div class="details">

	<div class="detail">
		<span class="title">Received:</span>
		<span><?php echo number_format($data['total_connections_received']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Connected:</span>
		<span><?php echo number_format($data['connected_clients']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Rejected:</span>
		<span><?php echo number_format($data['rejected_connections']) ?></span>
	</div>
	<div class="detail">
		<span class="title">Commands:</span>
		<span><?php echo number_format($data['total_commands_processed']) ?></span>
	</div>

	</div>
</div>

<div class='box col'>
	<h2>Persistence</h2>
	<p class="details">Changes since last save: <?php echo number_format($data['rdb_changes_since_last_save']) ?></p>
	<p class="details">Last saved<br /><?php echo ($data) ? time_elapsed(time() - $data['rdb_last_save_time']) : '0' ?> ago.</p>
</div>

</div>
<div class="grid">
<?php foreach ($redisDatabases as $i) { ?>
	<div class='box col'>
		<h2>Keys in store <em><?php echo "db$i" ?></em></h2>
		<div class="key">
		<?php
			$values = explode(',', $data["db$i"]);
			foreach ($values as $value)
			{
				debug($value, false);
				$kv = explode('=', $value, 2);
				$keyData[$kv[0]] = $kv[1];
			}
			echo $keyData['keys'];
		?>
		</div>
		<?php if (FLUSHDB === true) { ?>
		<button class="flushButton" id="flush<?php echo "$i" ?>" style="width: 100px;" value="<?php echo "$i" ?>" onclick="flushDB(<?php echo "$server" ?>, value);">Flush</button>
		<?php } ?>
	</div>
<?php } ?>
</div>

<?php if (FLUSHALL === true) { ?>
<div>
<button id="flushall" style="width: 150px;" onclick="flushDB(<?php echo "$server" ?>, -1);">FLUSH ALL</button>
</div>
<?php } ?>

<?php if (STATUS_LINE !== 'top') { ?>
<div id="msg" class='boxmsg col3' style="visibility: hidden;">
&nbsp;
</div>
<?php } ?>
<br>
<div id="allinfo" style="display: none;" class='box col3'>
	<h2>Details</h2>
	<div class="details">

	<?php
	foreach ($details as $section => $ddata)
	{
		echo "<h3>$section</h3>\n";
		foreach ($ddata as $key => $value)
		{
			echo '<div class="detail">'."\n";
			echo '<span class="key">' . $key.':' . '</span>'."\n";
			echo '<span>' . $value . '</span>'."\n";
			echo "</div>\n";
		}
	}
	?>

	</div>
</div>

</div>   <!-- Wrapper  -->
<footer>
	<a href="<?php echo URL; ?>" target="_blank"><?php echo URL; ?></a>
</footer>
<script>
var rate  = 0;
var play  = 0;
var delay = 1000;
var doc_rate = document.getElementById("rate");
var doc_play = document.getElementById("play");
var doc_msg  = document.getElementById('msg');
const errorColor   = '#F88';
const updateColor  = '#FFF7BA';
const successColor = '#C1FFC1';
const defaultColor = '#E6E6E6';
const CONFIRM_FLUSHDB = '<?php echo CONFIRM_FLUSHDB; ?>';
const CONFIRM_FLUSHALL = '<?php echo CONFIRM_FLUSHALL; ?>';
const FLUSHDB = '<?php echo FLUSHDB; ?>';
const FLUSHALL = '<?php echo FLUSHALL; ?>';
const URL = '<?php echo URL; ?>';

(function() {
var hitPie = createPie('174px',[{value: <?php echo $hitRate ?>, color: '#8892BF' }]);
document.getElementById('hitrate').appendChild(hitPie);
}());

function toggleDetails() {
	var state = document.getElementById('allinfo').style.display;
	if (state == 'inline-block') {
		document.getElementById('allinfo').style.display = 'none';
		localStorage.setItem('redisInfoDetails', 'false');
	} else {
		document.getElementById('allinfo').style.display = 'inline-block';
		localStorage.setItem('redisInfoDetails', 'true');
	}
}

function toggleAsync() {
	const checked = document.getElementById("checkboxasync").checked;

	if (checked) {
		localStorage.setItem('redisInfoFlushAsync', 'true');
		changeFlushButtons(checked);
	} else {
		localStorage.setItem('redisInfoFlushAsync', 'false');
		changeFlushButtons(checked);
	}
}

function changeFlushButtons(status) {
	if (status) {
		if (document.getElementById('flushall')) document.getElementById('flushall').innerHTML = 'FLUSH ALL ASYNC';
		const button = document.getElementsByClassName("flushButton");
		var i;
		for (i = 0; i < button.length; i++) {
			button[i].innerHTML = 'Flush Async';
		}
	} else {
		if (document.getElementById('flushall')) document.getElementById('flushall').innerHTML = 'FLUSH ALL';
		const button = document.getElementsByClassName("flushButton");
		var i;
		for (i = 0; i < button.length; i++) {
			button[i].innerHTML = 'Flush';
		}
	}
}

function initRedisInfo() {
	if (localStorage.getItem('redisInfoDetails') === 'true') document.getElementById('allinfo').style.display = 'inline-block';
	if (localStorage.getItem('redisInfoFlushAsync') === 'true') {
		changeFlushButtons(true);
		document.getElementById("checkboxasync").checked = true;
	}
	if (localStorage.getItem('redisInfoPlayDelay')) {
		doc_rate.value = localStorage.getItem('redisInfoPlayDelay');
	}
	if (sessionStorage.getItem('redisInfoPlay') == '1') { // we are still in auto refesh mode
		doc_rate.value = localStorage.getItem('redisInfoPlayDelay');
		doc_rate.disabled = true;
		doc_play.innerHTML = "Pause";
		play = 1;
		autorefresh();
	}
	defaultMsg();
}

function checkForUpdate() {
	var xmlhttp = new XMLHttpRequest();
	const req = 'check.php';
	xmlhttp.onreadystatechange = function() {
		if (this.readyState==4 && this.status == 200) {
			if (this.responseText != 'Error') {
				const response = this.responseText;
				if (response == '0') {
					doc_msg.style.visibility = 'visible';
					doc_msg.style.background = successColor;
					doc_msg.innerHTML        = 'Redis Stats is up to date.';
					setTimeout("defaultMsg()", 5000);
				} else {
					const text = 'Version ' + response + ' is <a href="' + URL + '" target="_blank">available</a>.';
					doc_msg.style.visibility = 'visible';
					doc_msg.style.background = updateColor;
					doc_msg.innerHTML        = text;
					setTimeout("defaultMsg()", 10000);
				}
			} else {
				doc_msg.style.visibility = 'visible';
				doc_msg.style.background = errorColor;
				doc_msg.innerHTML = 'Could not retrieve version information.';
				setTimeout("defaultMsg()", 5000);
			}
		}
	};
	xmlhttp.open("GET", req, true);
	xmlhttp.send();
}

function flushDB(server, db) {
	if ((db == -1) && CONFIRM_FLUSHALL) {
		if (!confirm("This will flush the entire Redis instance.\n\nAre you sure?")) {
			return;
		}
	}
	if (CONFIRM_FLUSHDB) {
		if (!confirm("This will flush db"+db+"\n\nAre you sure?")) {
			return;
		}
	}
	var flushAsync = (localStorage.getItem('redisInfoFlushAsync') === 'true') ? 1 : 0;
	const tid = '<?php echo $id; ?>';
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function() {
		if (this.readyState==4 && this.status == 200) {
			if (this.responseText == 'Success') {
				if (db != -1) {
					document.getElementById('flush'+db).innerHTML        = 'Flushed';
					document.getElementById('flush'+db).style.background = successColor;
					doc_msg.style.visibility                             = 'visible';
					doc_msg.style.background                             = successColor;
				} else {
					document.getElementById('flushall').innerHTML        = 'ALL Flushed';
					document.getElementById('flushall').style.background = successColor;
					doc_msg.style.visibility                             = 'visible';
					doc_msg.style.background                             = successColor;
				}
				doc_msg.innerHTML = '+OK';
				setTimeout("location.reload()", 2500);
			} else {
				if (db != -1) {
					document.getElementById('flush'+db).style.background = errorColor;
				} else {
					document.getElementById('flushall').style.background = errorColor;
				}
				doc_msg.style.visibility = 'visible';
				doc_msg.style.background = errorColor;
				doc_msg.innerHTML = this.responseText;
			}
		}
	};
	const req = 'flushdb.php?id='+tid+'&s='+server+'&db='+db+'&async='+flushAsync;
	xmlhttp.open("GET", req, true);
	xmlhttp.send();
}

function autorefresh() {
	delay = parseInt(doc_rate.value);
	if (!delay || delay < 1) {
		doc_msg.style.visibility = 'visible';
		doc_msg.style.background = errorColor;
		doc_msg.innerHTML = "Not a valid 'refresh' value.";
		return;
	}
	play = 1;
	doc_rate.disabled = true;
	doc_play.innerHTML = "Pause";
	localStorage.setItem('redisInfoPlayDelay', delay);
	sessionStorage.setItem('redisInfoPlay', play);
	setTimeout("callback()", delay * 1000);
}

function playpause() {
	rate = 0;
	if (play) {
		play = 0;
		doc_play.innerHTML = "Play";
		doc_rate.disabled = false;
		sessionStorage.setItem('redisInfoPlay', play);
	} else {
		autorefresh();
	}
}

function callback() {
	if (!play) return;
	setTimeout("location.reload()", delay * 1000);
}

function inputMsgSuccess() {
	doc_msg.style.visibility = 'hidden';
	doc_rate.style.background = null;
	doc_msg.innerHTML = "";
	defaultMsg();
}

function inputMsgError() {
	doc_msg.style.visibility = 'visible';
	doc_msg.style.background = errorColor;
	doc_msg.innerHTML = "Not a valid 'refresh' rate. The value must be > 0 and <= 86400.";
}

function defaultMsg() {
	if (FLUSHDB || FLUSHALL) {
		var text = [];
		doc_msg.style.visibility = 'visible';
		doc_msg.style.background = defaultColor;
		if (FLUSHDB) {
			text.push('confirm flushing db: ' + (CONFIRM_FLUSHDB ? 'ON' : 'OFF'));
		}
		if (FLUSHALL) {
			text.push('confirm flushing instance: ' + (CONFIRM_FLUSHALL ? 'ON' : 'OFF'));
		}
		const comment = text.join('<span style="display: inline-block; width: 50px;"></span>');
		doc_msg.textAlign = 'center';
		doc_msg.innerHTML = comment;
	}
}

function myInputTest() {
	var x = doc_rate.value;
	if (x == '') {
		doc_play.disabled = true;
		inputMsgSuccess();
		return;
	}
	if (/\D/.test(x) || x < 1 || x > 86400 ) {
		doc_play.disabled = true;
		if (x != '') {
			inputMsgError();
		}
	} else {
		doc_play.disabled = false;
		inputMsgSuccess();
	}
}

</script>
</body>
</html>
