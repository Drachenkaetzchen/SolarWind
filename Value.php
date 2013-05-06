<?php

/**
 * Represents a summarized value.
 *
 * Logs arbitrary values as sum as well as the number of datapoints, and is able to return the average and sum of all
 * logged values.
 *
 * This class does not log individual values, as memory is limited on the OpenWRT device.
 */
class Value {
    private $unit;
    private $sum;
    private $numDatapoints;

    /**
     * Creates a new value. The unit is a string which defines the unit, e.g. "Wh", "V" or "°C".
     *
     * @param $unit
     */
    public function __construct ($unit) {
        $this->unit = $unit;
        $this->sum = 0;
        $this->numDatapoints = 0;
    }

    /**
     * Logs a float value.
     * @param $value
     */
    public function logValue ($value) {
        $this->sum += (float)$value;
        $this->numDatapoints++;
    }

    /**
     * Gets the average of all logged values
     * @return float
     */
    public function getAverage () {
        $average = $this->sum / $this->numDatapoints;


        return $average;
    }

    /**
     * Gets the sum of all logged values
     * @return int
     */
    public function getSum () {
        return $this->sum;
    }

    /**
     * Resets all data
     */
    public function resetData () {
        $this->numDatapoints = 0;
        $this->sum = 0;
    }
}