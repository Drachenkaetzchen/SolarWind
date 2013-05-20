<?php
include("php_serial.class.php");
include("config.php");
include("DataReader.php");
include("Value.php");

$serial = initSerial($port);

$data = array();
$datapoints = array();
$counter = 0;

$dataReader = new DataReader();

$fp = fopen($logfile, "w+");

while (1) {
	$read = $serial->readPort();
	
	if ($read) {
		if (trim($read) != "") {
            $dataReader->parseLine($read);
		}

		// 100 datapoints equals about one minute
		if ($counter > 100) {
			$counter = 0;
			$pushData = $dataReader->getPushData();

			pushToCosm($pushData, $apikey, $cosmuri);
		}

		$counter++;
	}

	usleep(100000);
}

function dlog ($line) {
	global $fp;

	fputs($fp, $line ."\n");
}

function initSerial ($port) {
	$serial = new phpSerial();
	$serial->deviceSet($port);

	$serial->confBaudRate(9600);
	$serial->confParity("none");
	$serial->confCharacterLength(8);
	$serial->confStopBits(1);
	$serial->confFlowControl("none");

	$serial->deviceOpen();

	return $serial;
}


function pushToCosm ($data, $apikey, $uri) {
	$outData = array();

	foreach ($data as $key => $value) {
		$outData[] = array(
			"id" => $key,
			"current_value" => $value
		);
	}

	$data = array(
		"version" => "1.0.0",
		"datastreams" => $outData
	);

	file_put_contents("/tmp/cosm.json", json_encode($data));
        $cli = 'curl --retry 2 --request PUT -k --data-binary @/tmp/cosm.json --header "X-ApiKey: '.$apikey.'" '.$uri;

	dlog($cli);
	dlog(json_encode($data));

	exec($cli);

	// We can't use date() here, because tzdata is missing on the carambola openwrt distribution
	$date = trim(`date -Iseconds`);

	$cli2 = 'scp -i /root/.ssh/id_rsa /tmp/cosm.json "felicitus@172.22.117.181:/share/solarwind/'.$date.'.json"';
	exec($cli2);
}
