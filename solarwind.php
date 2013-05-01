<?php
include("php_serial.class.php");
include("config.php");

$serial = initSerial($port);

$data = array();
$datapoints = array();
$counter = 0;

while (1) {
	$read = $serial->readPort();
	
	if ($read) {
		if (trim($read) != "") {
			parseLine($read, $data, $datapoints);
		}

		if ($counter > 50) {
			$counter = 0;

			$pushData = array(
				"temperature" => $data["temperature"] / $datapoints["temperature"],
				"current_battery" => $data["current_battery"] / $datapoints["current_battery"],
				"voltage_battery" => $data["voltage_battery"] / $datapoints["voltage_battery"],
				"current_cell" => $data["current_cell"] / $datapoints["current_cell"],
				"voltage_cell" => $data["voltage_cell"] / $datapoints["voltage_cell"]
			);

			pushToCosm($pushData, $apikey, $cosmuri);

			$data = array();
			$datapoints = array();
		}

		$counter++;
	}

	sleep(0.1);
}


function parseLine ($line, &$data, &$datapoints) {
	$parts = explode(": ", $line);

	switch ($parts[0]) {
		case "T":
			$data["temperature"] += floatval($parts[1]);
			$datapoints["temperature"]++;
			break;
		case "0":
			$chan0 = explode(" ", $parts[1]);
			$volts = floatval(str_replace("V", "", $chan0[0]));
			$amps = floatval(str_replace("mA", "", $chan0[1]));
			
			$data["voltage_cell"] += $volts;
			$datapoints["voltage_cell"]++;
			$data["current_cell"] += $amps;
			$datapoints["current_cell"]++;
			break;
		case "1":
			$chan0 = explode(" ", $parts[1]);
                        $volts = floatval(str_replace("V", "", $chan0[0]));
                        $amps = floatval(str_replace("mA", "", $chan0[1]));

                        $data["voltage_battery"] += $volts;
                        $datapoints["voltage_battery"]++;
                        $data["current_battery"] += $amps;
                        $datapoints["current_battery"]++;
			break;

	}
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
        $cli = 'curl --request PUT --data-binary @/tmp/cosm.json --header "X-ApiKey: '.$apikey.'" '.$uri;

	exec($cli);
}
