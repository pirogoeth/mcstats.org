<?php
// Emits JSON
define('ROOT', '../');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

if (!isset($_GET['plugin']))
{
    exit('ERR No plugin provided.');
}

// Load the plugin
$plugin = loadPlugin($_GET['plugin']);

// Doesn't exist
if ($plugin === NULL)
{
    exit('ERR Invalid plugin.');
}

if (!isset($_GET['hours']))
{
    exit('ERR No amount of days provided.');
}

// Amount of days to go back in past
$hours = intval($_GET['hours']);

if ($hours <= 0 || $hours > 744)
{
    exit('ERR Not supported.');
}


$json = array();

// Calculate the closest hour
$denom = 60 * 60; // 60 minutes * 60 seconds = 3600 seconds in an hour
$baseEpoch = round(time() / $denom) * $denom;

// calculate the minimum
$minimum = strtotime('-' . $hours . ' hours', $baseEpoch);
$maximum = $baseEpoch;

// An array of the column names so the client knows what to use
$json['columns'] = array();

// the actual data
// breaks down into json[data][epoch][columnID]
$json['data'] = array();

foreach ($plugin->getCustomColumns() as $id => $name)
{
    // store the column name
    $json['columns'][] = $name;

    // load the datapoints from the database
    $dataPoints = $plugin->getTimelineCustom($id, $minimum, $maximum);

    foreach ($dataPoints as $epoch => $dataPoint)
    {
        $json['data'][$epoch][$id] = $dataPoint;
    }
}

// output the json
echo json_encode($json);