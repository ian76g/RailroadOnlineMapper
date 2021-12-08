<?php

class SaveReader
{
    private array $data;
    private int $totalTrackLength = 0;
    private float $maxSlope = 0;
    private int $totalSwitches = 0;
    private int $totalLocos = 0;
    private int $totalCarts = 0;
    private int $initialsTreeDown = 1500;

    /**
     * Mapper constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    private function getUserIpAddr()
    {
        global $argv;

        if (isset($argv) && $argv) {
            return 'local';
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * @param $filename
     * @param bool $public
     * @param array $tasks
     * @return void
     */
    function addDatabaseEntry($filename, $public = false, array $tasks)
    {
        $this->getTrackLength();
        $this->getSwitchesCount();
        $this->getRollingStockCount();
        $sum=0;
        foreach($tasks[0] as $task){
            $sum+=$task[1];
        }
        // create a "database" and store some infos about this file for the websies index page
        $db = @unserialize(@file_get_contents('db.db'));
        $db[$filename] = array(
            $this->totalTrackLength,
            $this->totalSwitches,
            $this->totalLocos,
            $this->totalCarts,
            $this->maxSlope,
            $this->getUserIpAddr(),
            (count($this->data['Removed']['Vegetation']) - $this->initialsTreeDown),
            $public,
            sizeof($tasks[0]),
            $sum
        );
        file_put_contents('db.db', serialize($db));
    }

    function updateDatabaseEntry($filename, array $tasks)
    {
        $this->getTrackLength();
        $this->getSwitchesCount();
        $this->getRollingStockCount();
        $sum=0;
        foreach($tasks[0] as $task){
            $sum+=$task[1];
        }
        // create a "database" and store some infos about this file for the websies index page
        $db = @unserialize(@file_get_contents('db.db'));
        $db[$filename] = array(
            $this->totalTrackLength,
            $this->totalSwitches,
            $this->totalLocos,
            $this->totalCarts,
            $this->maxSlope,
            $this->getUserIpAddr(),
            (count($this->data['Removed']['Vegetation']) - $this->initialsTreeDown),
            $db[$filename][7],
            sizeof($tasks[0]),
            $sum
        );
        file_put_contents('db.db', serialize($db));
    }

    /**
     * @return void
     */
    private function getTrackLength()
    {
        if (isset($this->data['Splines'])) {
            foreach ($this->data['Splines'] as $spline) {
                $type = $spline['Type'];
                $segments = $spline['Segments'];

                foreach ($segments as $segment) {
                    if ($segment['Visible'] != 1) continue; // skip invisible tracks

                    $distance = sqrt(
                        pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                        pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2) +
                        pow($segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'], 2)
                    );

                    if (in_array($type, array(4, 0))) {
                        $this->totalTrackLength += $distance;

                        $height = $segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'];
                        $height = abs($height);
                        $length = sqrt(pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                            pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2));


                        if (!empty($length)) {
                            $slope = ($height * 100 / $length);
                            $this->maxSlope = max($this->maxSlope, $slope);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    private function getSwitchesCount()
    {
        if (array_key_exists('Switchs', $this->data)) {
            $this->totalSwitches = count($this->data['Switchs']);
        }
    }

    /**
     * @return void
     */
    private function getRollingStockCount()
    {
        $vehicles = array_values($this->data['Frames']);
        foreach ($vehicles as $vehicle) {
            if (
                $vehicle['Type'] == 'porter_040'
                || $vehicle['Type'] == 'porter_042'
                || $vehicle['Type'] == 'handcar'
                || $vehicle['Type'] == 'eureka'
                || $vehicle['Type'] == 'climax'
                || $vehicle['Type'] == 'heisler'
                || $vehicle['Type'] == 'class70'
                || $vehicle['Type'] == 'cooke260'
            ) {
                $this->totalLocos++;
            } else {
                $this->totalCarts++;
            }
        }
    }
}
