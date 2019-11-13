<?php
// CONFIG
$servers = array(
	array('Local', '127.0.0.1', 6379),
);
// END CONFIG

$server = 0;
if (isset($_GET['s']) && intval($_GET['s']) < count($servers)) {
	$server = intval($_GET['s']);
}

$fp = fsockopen($servers[$server][1], $servers[$server][2], $errno, $errstr, 30);

$data = array();

if (!$fp) {
    die($errstr);
} else {
    fwrite($fp, "INFO\r\nQUIT\r\n");
    while (!feof($fp)) {
        $info = explode(':', trim(fgets($fp)), 2);
        if (isset($info[1])) $data[$info[0]] = $info[1];
    }
    fclose($fp);
}

function time_elapsed($secs){
    $bit = array(
        ' year'      => $secs / 31556926 % 12,
        ' week'      => $secs / 604800 % 52,
        ' day'       => $secs / 86400 % 7,
        ' hour'      => $secs / 3600 % 24,
        ' minute'    => $secs / 60 % 60,
        ' second'    => $secs % 60,
    );
       
    foreach ($bit as $k => $v){
        if($v > 1) $ret[] = $v . $k . 's';
        if($v == 1) $ret[] = $v . $k;
    }
    array_splice($ret, count($ret) - 1, 0, 'and');
   
    return implode(' ', $ret);
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="utf-8" />
 <title>Redis Stats</title>
 <style type="text/css">
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
  	background:  #8892BF;
  	border-bottom: 4px solid #4F5B93;
  	padding: 0.25em;
  	margin-top:0;
  	color: #22242F;
  }
  
  form {
  	margin: 1em 0;
  }
  
  .wrapper {
  	text-align:center;
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
  
  .col {
  	width: 224px;
  	display: inline-block;
  }
  
  .col2 {
  	width: 460px;
  	display: inline-block;
  }
  
  .col2 .col {
  	vertical-align: middle;
  }
  
  .col > * {
  	padding: 0 4px 4px;
  }
  
  .box h2 {
  	margin:0 0 0.5em;
  	padding: 0.25em;
  	font-size: 1.15em;
  	background: #E2E4EF;
  	border-top:	 2px solid #4F5B93;
  	border-bottom: 1px solid #C4C9DF;
  }

  .key {
    font-weight: bold;
    font-size: 42px;
  }
  
  .details { margin 1em 0; }

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
<body>
<div class="wrapper">
<h1>Redis Stats</h1>
<form action="" method="get">
<label for="server">Server:</label>
<select onchange="this.form.submit()" id="server" name="s">
<?php foreach ($servers as $i => $serv): ?>
<option value="<?php echo $i ?>"<?php echo ($server == $i) ? ' selected="selected"' : '' ?>><?php echo $serv[0] ?></option>
<?php endforeach; ?>
</select>
</form>
<div class="grid">
<div class='box col2'>
    <h2>Hits</h2>
    <div class="grid">
    <div id="hitrate" class="col">
    <?php $hitRate = sprintf('%.1f' , $data['keyspace_hits'] / ($data['keyspace_hits'] + $data['keyspace_misses']) * 100); ?>
    <div class="key"><?php echo $hitRate ?>%</div>
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
</div>

<div class='box col'>
    <h2>Used Memory</h2>
    <div class="key"><?php echo $data['used_memory_human'] ?></div>
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
    <p class="details">Last saved<br /><?php echo time_elapsed(time() - $data['rdb_last_save_time']) ?> ago.</p>
</div>

</div>
<div class="grid">
<?php for ($i = 0; isset($data["db$i"]); $i++): ?>
<div class='box col'>
    <h2>Keys in store <em><?php echo "db$i" ?></em></h2>
    <div class="key">
        <?php
            $values = explode(',', $data["db$i"]);
            foreach($values as $value) {
                $kv = explode('=', $value, 2);
                $keyData[$kv[0]] = $kv[1];
            }
            echo $keyData['keys'];
        ?>
    </div>
</div>
<?php endfor; ?>
</div>
</div>
<script>
(function() {
var hitPie = createPie('174px',[{value: <?php echo $hitRate ?>, color: '#8892BF' }]);
document.getElementById('hitrate').appendChild(hitPie);
}());
</script>
</body>
</html>
