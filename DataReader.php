<?php

class DataReader {
    private $temperature;
    private $voltage0;
    private $voltage1;
    private $current0;
    private $current1;
    private $usedWattHours;
    private $harvestedWattHours;
    private $currentDate;

    private $startupToday = true;
    private $startupTime;

    public function __construct () {
        $this->temperature = new Value("°C");
        $this->voltage0 = new Value("V");
        $this->voltage1 = new Value("V");
        $this->current0 = new Value("mA");
        $this->current1 = new Value("mA");
        $this->usedWattHours = new Value("Wh");
        $this->harvestedWattHours = new Value("Wh");
        $this->currentDate = $this->getDate();

        $this->startupTime = `date +%H:%M:%S`;
    }

    public function getPushData () {
        $roundPrecision = 2;

        $this->usedWattHours->logValue(($this->current1->getAverage() / 1000) * $this->voltage1->getAverage());
        $this->harvestedWattHours->logValue(($this->current0->getAverage() / 1000) * $this->voltage0->getAverage());

        $pushData = array(
            "temperature" => round($this->temperature->getAverage(),$roundPrecision),
            "current_battery" => round($this->current1->getAverage(),$roundPrecision),
            "voltage_battery" => round($this->voltage1->getAverage(),$roundPrecision),
            "current_cell" => round($this->current0->getAverage(),$roundPrecision),
            "voltage_cell" => round($this->voltage0->getAverage(),$roundPrecision),
            "power_cell" => round($this->current0->getAverage() * $this->voltage0->getAverage() / 1000, $roundPrecision),
            "power_circuit" => round($this->current1->getAverage() * $this->voltage1->getAverage() / 1000, $roundPrecision),
            "daily_used_wh" => round($this->usedWattHours->getAverage() * $this->getHoursSinceMidnight(), $roundPrecision),
            "daily_harvested_wh" => round($this->harvestedWattHours->getAverage() * $this->getHoursSinceMidnight(), $roundPrecision),
            "watt_hour_difference" =>round(($this->harvestedWattHours->getAverage() * $this->getHoursSinceMidnight()) - ($this->usedWattHours->getAverage() * $this->getHoursSinceMidnight()), $roundPrecision),

        );

        $this->temperature->resetData();
        $this->current0->resetData();
        $this->current1->resetData();
        $this->voltage0->resetData();
        $this->voltage1->resetData();

        // Reset Wh calculations on midnight
        if ($this->currentDate != $this->getDate()) {
            $this->startupToday = false;
            $this->usedWattHours->resetData();
            $this->harvestedWattHours->resetData();
            $this->currentDate = $this->getDate();
        }

        return $pushData;
    }

    /*
     * Returns the amount of second since startup or midnight.
     *
     * Yes, the method name is a bit misleading.
     */
    public function getSecondsSinceMidnight () {
        // Workaround because tzdata is broken on OpenWRT
        $datestring = `date +%H:%M:%S`;

        list($hour, $minute, $second) = explode(":", $datestring);

        if ($this->startupToday) {
            list($shour, $sminute, $ssecond) = explode(":", $this->startupTime);


            return ($hour * 3600 + $minute * 60 + $second) - ($shour * 3600 + $sminute * 60 + $ssecond);
        } else {
            return ($hour * 3600 + $minute * 60 + $second);
        }

    }

    /**
     * Returns the amount of hours since startup or midnight.
     *
     * @return float
     */
    public function getHoursSinceMidnight () {
        return $this->getSecondsSinceMidnight() / 3600;
    }

    public function getDate () {
        return `date +%Y-%m-%d`;
    }
    
    public function parseLine ($line) {
        $parts = explode(": ", $line);

        switch ($parts[0]) {
            case "T":
                $this->temperature->logValue($parts[1]);
                break;
            case "0":
                $chan0 = explode(" ", $parts[1]);
                $volts = floatval(str_replace("V", "", $chan0[0]));
                $amps = floatval(str_replace("mA", "", $chan0[1]));

                $this->voltage0->logValue($volts);
                $this->current0->logValue($amps);
                break;
            case "1":
                $chan0 = explode(" ", $parts[1]);
                $volts = floatval(str_replace("V", "", $chan0[0]));
                $amps = floatval(str_replace("mA", "", $chan0[1]));

                $this->voltage1->logValue($volts);
                $this->current1->logValue($amps);
                break;
        }
    }
}