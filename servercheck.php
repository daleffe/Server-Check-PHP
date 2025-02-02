<?php
function wmic($alias = '', $method = '', $default = false, $verb = 'get') {
	if (!empty(trim($alias)) && !empty(trim($alias)) && !empty(trim($alias)))
	{
		@exec("wmic " . $alias . " " . $verb . " " . $method . ($verb == "get" ? " /value" : ""), $output);

		$output = is_array($output) ? array_values(array_filter($output)) : false;

		if ($output) foreach ($output as $line) if (preg_match("/" . $method . "=" . "/i",trim($line))) return trim(str_ireplace($method . "=","",$line));
	}

	return $default;
}

if (!defined('SERVERCHECK_AS_LIB')) {
	$server_check_version = '1.0.6';
	$start_time = microtime(TRUE);
}

$cpuload = 0;
$cpu_count = 0;
$memavailable = 0;
$memused = 0;
$memtotal = 0;

if (PHP_OS_FAMILY === 'Windows') {
	// I don't know if is really this extension name
	if (extension_loaded('COM')) {
		// Win CPU
		$wmi = new COM('WinMgmts:\\\\.');
		$cpus = $wmi->InstancesOf('Win32_Processor');
		foreach ($cpus as $key => $cpu) {
			$cpuload += $cpu->LoadPercentage;
			$cpu_count++;
		}
		// WIN MEM
		$res = $wmi->ExecQuery('SELECT FreePhysicalMemory,FreeVirtualMemory,TotalSwapSpaceSize,TotalVirtualMemorySize,TotalVisibleMemorySize FROM Win32_OperatingSystem');
		$mem = $res->ItemIndex(0);
		$memtotal = $mem->TotalVisibleMemorySize;
		$memavailable = $mem->FreePhysicalMemory;
	} else {
		// CPU Threads
		$cpu_count = intval(wmic("cpu","numberoflogicalprocessors",0));

		// CPU load
		$cpuload = intval(wmic("cpu","loadpercentage",0));

		// Memory usage
		$memtotal = intval(wmic("ComputerSystem","TotalPhysicalMemory",0));
		$memavailable = intval(wmic("OS","FreePhysicalMemory",0));
		if ($memavailable > 0) $memavailable *= 1024;
	}

	$memtotal = $memtotal > 0 ? round($memtotal / 1000000,2) : 0.0;
	$memavailable = $memavailable > 0 ? round($memavailable / 1000000,2) : 0.0;
	$memused = round($memtotal-$memavailable,2);

	// WIN CONNECTIONS
	$connections = shell_exec('netstat -nt | findstr :' . $_SERVER['SERVER_PORT'] . ' | findstr ESTABLISHED | find /C /V ""');
	if (empty(trim($connections))) $connections = shell_exec('(Get-NetTCPConnection | Where-Object { $_.RemotePort -eq ' . "'" . $_SERVER['SERVER_PORT'] . "'" . ' -and $_.State -eq ' . "'" . 'ESTABLISHED' . "'" . '}).count');
	$totalconnections = shell_exec('netstat -nt | findstr :' . $_SERVER['SERVER_PORT'] . ' | find /C /V ""');
	if (empty(trim($totalconnections))) $totalconnections = shell_exec('(Get-NetTCPConnection | Where-Object { $_.RemotePort -eq ' . "'" . $_SERVER['SERVER_PORT'] . "'" . '}).count');

	$connections = is_numeric($connections) ? intval($connections) : 0;
	$totalconnections = is_numeric($totalconnections) ? intval($totalconnections) : 0;
} else {
	// Linux CPU
	$cpuload = sys_getloadavg()[0] * 100;
    $cpuload > 100 ? 100 : ($cpuload < 0 ? 0 : $cpuload);

	$cpu_count = shell_exec('nproc');
	// Linux MEM
	$free = shell_exec('free');
	$free = (string)trim($free);
	$free_arr = explode("\n", $free);
	$mem = explode(" ", $free_arr[1]);
	$mem = array_filter($mem, function($value) { return ($value !== null && $value !== false && $value !== ''); }); // removes nulls from array
	$mem = array_merge($mem); // puts arrays back to [0],[1],[2] after
	$memtotal = round($mem[1] / 1000000,2);
	$memused = round($mem[2] / 1000000,2);
	$memfree = round($mem[3] / 1000000,2);
	$memshared = round($mem[4] / 1000000,2);
	$memcached = round($mem[5] / 1000000,2);
	$memavailable = round($mem[6] / 1000000,2);
	// Linux Connections
	$connections = `netstat -ntu | grep :`  . $_SERVER['SERVER_PORT'] . ` | grep ESTABLISHED | grep -v LISTEN | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -rn | grep -v 127.0.0.1 | wc -l`;
	$totalconnections = `netstat -ntu | grep :`  . $_SERVER['SERVER_PORT'] . `| grep -v LISTEN | awk '{print $5}' | cut -d: -f1 | sort | uniq -c | sort -rn | grep -v 127.0.0.1 | wc -l`;
}

$memusage = round(($memused/$memtotal)*100);

$phpload = round(memory_get_usage() / 1000000,2);

$diskfree = round(disk_free_space(stristr(PHP_OS, "win") ? getenv("SystemDrive") : ".") / 1000000000);
$disktotal = round(disk_total_space(stristr(PHP_OS, "win") ? getenv("SystemDrive") : ".") / 1000000000);
$diskused = round($disktotal - $diskfree);

$diskusage = round($diskused/$disktotal*100);

if (!defined('SERVERCHECK_AS_LIB')) {
	if ($memusage > 85 || $cpuload > 85 || $diskusage > 85) {
		$trafficlight = 'red';
	} elseif ($memusage > 50 || $cpuload > 50 || $diskusage > 50) {
		$trafficlight = 'orange';
	} else {
		$trafficlight = '#2F2';
	}

	$end_time = microtime(TRUE);
	$time_taken = $end_time - $start_time;
	$total_time = round($time_taken,4);
}

$server_check = array(
	'ram_usage' => $memusage, 'cpu_load' => $cpuload, 'disk_usage' => $diskusage,
	'connections' => $connections, 'total_connections' => $totalconnections,
	'cpu_threads' => $cpu_count,
	'ram_total' => $memtotal, 'ram_used' => $memused, 'ram_available' => $memavailable,
	'disk_free' => $diskfree, 'disk_used' => $diskused, 'disk_total' => $disktotal,
	'php_load' => $phpload,
);

if (defined('SERVERCHECK_AS_LIB')) return $server_check;

// use servercheck.php?json=1
if (isset($_GET['json'])) {
	header("Content-Type: application/json");
	echo json_encode($server_check);
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ServerCheck</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
html {
	background: #FFF;
}
body {
	background: #FFF;
	font-family: Arial,sans-serif;
	margin: 0;
	padding: 0;
	color: #333;
}
#container {
	width: 320px;
	margin: 10px auto;
	padding: 10px 20px;
	background: #EFEFEF;
	border-radius: 5px;
	box-shadow: 0 0 5px #AAA;
	-webkit-box-shadow: 0 0 5px #AAA;
	-moz-box-shadow: 0 0 5px #AAA;
	box-sizing: border-box;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
}
.description {
	font-weight: bold;
}
#trafficlight {
	float: right;
	margin-top: 15px;
	width: 50px;
	height: 50px;
	border-radius: 50px;
	background: <?php echo $trafficlight; ?>;
	border: 3px solid #333;
}
#details {
	font-size: 0.8em;
}
hr {
	border: 0;
	height: 1px;
	background-image: linear-gradient(to right, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0));
}
.big {
	font-size: 1.2em;
}
.footer {
	font-size: 0.5em;
	color: #888;
	text-align: center;
}
.footer a {
	color: #888;
}
.footer a:visited {
	color: #888;
}
.dark {
	background: #000;
	filter: invert(1) hue-rotate(180deg);
}
</style>
</head>
<body>
	<div id="container">
		<div id="trafficlight" class="nodark"></div>

		<p><span class="description big">🌡️ RAM Usage:</span> <span class="result big"><?php echo $memusage; ?>%</span></p>
		<p><span class="description big">🖥️ CPU Usage: </span> <span class="result big"><?php echo $cpuload; ?>%</span></p>
		<p><span class="description">💽 Hard Disk Usage: </span> <span class="result"><?php echo $diskusage; ?>%</span></p>
		<p><span class="description">🖧 Established Connections: </span> <span class="result"><?php echo $connections; ?></span></p>
		<p><span class="description">🖧 Total Connections: </span> <span class="result"><?php echo $totalconnections; ?></span></p>
		<hr>
		<p><span class="description">🖥️ CPU Threads:</span> <span class="result"><?php echo $cpu_count; ?></span></p>
		<hr>
		<p><span class="description">🌡️ RAM Total:</span> <span class="result"><?php echo $memtotal; ?> MB</span></p>
		<p><span class="description">🌡️ RAM Used:</span> <span class="result"><?php echo $memused; ?> MB</span></p>
		<p><span class="description">🌡️ RAM Available:</span> <span class="result"><?php echo $memavailable; ?> MB</span></p>
		<hr>
		<p><span class="description">💽 Hard Disk Free:</span> <span class="result"><?php echo $diskfree; ?> GB</span></p>
		<p><span class="description">💽 Hard Disk Used:</span> <span class="result"><?php echo $diskused; ?> GB</span></p>
		<p><span class="description">💽 Hard Disk Total:</span> <span class="result"><?php echo $disktotal; ?> GB</span></p>
		<hr>
		<div id="details">
			<p><span class="description">📟 Server Name: </span> <span class="result"><?php echo $_SERVER['SERVER_NAME']; ?></span></p>
			<p><span class="description">💻 Server Addr: </span> <span class="result"><?php echo $_SERVER['SERVER_ADDR']; ?></span></p>
			<p><span class="description">🌀 PHP Version: </span> <span class="result"><?php echo phpversion(); ?></span></p>
			<p><span class="description">🏋️ PHP Load: </span> <span class="result"><?php echo $phpload; ?> MB</span></p>

			<p><span class="description">⏱️ Load Time: </span> <span class="result"><?php echo $total_time; ?> sec</span></p>
		</div>
	</div>
<footer>
	<div class="footer">
		<a href="https://github.com/daleffe/Server-Check-PHP" target="_blank">Server Check PHP</a> v <?php echo $server_check_version; ?> |
		Built by <a href="https://jamesbachini.com" target="_blank">James Bachini</a> and modified by <a href="https://github.com/daleffe" target="_blank">Guilherme R. Daleffe</a> | <a href="?json=1">JSON</a> | 🌙 <a href="javascript:void(0)" onclick="toggleDarkMode();">Dark Mode</a>
	</div>
</footer>
<script>
const toggleDarkMode = () => {
	if (localStorage.getItem('darkMode') && localStorage.getItem('darkMode') === 'true') {
		localStorage.setItem('darkMode',false);
	} else {
		localStorage.setItem('darkMode',true);
	}
	setDarkMode();
}
const setDarkMode = () => {
	if (localStorage.getItem('darkMode') && localStorage.getItem('darkMode') === 'true') {
		document.documentElement.classList.add('dark');
	} else {
		document.documentElement.classList.remove('dark');
	}
}
setDarkMode();
</script>
</body>
</html>