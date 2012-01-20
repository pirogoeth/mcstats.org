<?php
define('ROOT', './');

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

$name = $plugin->getName(); ?>

<html>
    <head>
        <title><?php echo $name; ?> Statistics</title>
        <link href="/static/css/main.css" rel="stylesheet" type="text/css" />

        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            google.load("jquery", "1.7.1");
            google.load('visualization', '1.0', {'packages':['corechart']});
            google.setOnLoadCallback(drawCharts);

            /**
             * Convert epoch time to a Date object to be used for graphing
             * @param epoch
             * @return Date
             */
            function epochToDate(epoch)
            {
                var date = new Date(0);
                date.setUTCSeconds(epoch);
                return date;
            }

            /**
             * Draw the charts on the page
             */
            function drawCharts()
            {
                generateCoverage();
            }

            function generateCustomData()
            {
                $.getJSON('/timeline-custom/<?php echo $name; ?>/144', function(json) {
                    var graph = new google.visualization.DataTable();
                    graph.addColumn('datetime', 'Day');
                    console.log(json);

                    // Add the columns
                    $.each(json.columns, function(i, v) {
                        graph.addColumn('number', v);
                    });

                    // iterate through the JSON data
                    $.each(json.data, function(i, v) {
                        // The graph row
                        var date = epochToDate(parseInt(i));
                        var row = [date];

                        $.each(v, function(i, v) {
                            row.push(parseInt(v));
                        });
                        console.log(row);

                        // add it to the graph
                        graph.addRow(row);
                    });

                    var options = {
                        width: '80%', height: 500,
                        chartArea: {width: '70%'}, title: 'Custom Data'
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('custom_timeline'));
                    chart.draw(graph, options);
                });
            }

            /**
             * Generate the timeline coverage for player/server counts
             */
            function generateCoverage()
            {
                $.getJSON('/coverage/<?php echo $name; ?>/144', function(json) {
                    var graph = new google.visualization.DataTable();
                    graph.addColumn('datetime', 'Day');
                    graph.addColumn('number', 'Active Servers');
                    graph.addColumn('number', 'Active Players');

                    // iterate through the JSON data
                    $.each(json, function(i, v) {
                        // extract data
                        var date = epochToDate(parseInt(v.epoch));
                        var servers = parseInt(v.servers);
                        var players = parseInt(v.players);

                        // add it to the graph
                        graph.addRow([date, servers, players]);
                    });

                    var options = {
                        width: '80%', height: 500,
                        chartArea: {width: '70%'}, title: 'Global Statistics'
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('coverage_timeline'));
                    chart.draw(graph, options);
                });
            }
        </script>
    </head>

<?php
echo '    <body>
        <h3>Plugin information</h3>
        <table>
            <tr> <td> Name </td> <td> ' . $name . ' </td> </tr>
            <tr> <td> Author </td> <td> ' . $plugin->getAuthor() . ' </td> </tr>
            <tr> <td> Global starts </td> <td> ' . number_format($plugin->getGlobalHits()) . ' </td> </tr>
        </table>

        <h3>Servers using ' . $name . '</h3>
        <table>
            <tr> <td> All-time </td> <td> ' . number_format($plugin->countServers()) . ' </td> </tr>
            <tr> <td> Last hour </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HOUR)) . ' </td> </tr>
            <tr> <td> Last 12 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_HALFDAY)) . ' </td> </tr>
            <tr> <td> Last 24 hrs </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_DAY)) . ' </td> </tr>
            <tr> <td> Last 7 days </td> <td> ' . number_format($plugin->countServersLastUpdated(time() - SECONDS_IN_WEEK)) . ' </td> </tr>
            <tr> <td> This month </td> <td> ' . number_format($plugin->countServersLastUpdated(strtotime(date('m').'/01/' . date('Y') . ' 00:00:00'))) . ' </td> </tr>
        </table>

        <div id="coverage_timeline" style="height:500"></div>
';

if (count($plugin->getCustomColumns()) > 0)
{
    echo '        <div id="custom_timeline" style="height:500"></div> <script> generateCustomData(); </script>
';
}

echo '        <h3>Servers\' last known version</h3>
        <p> Versions with less than 5 servers are omitted. <br/> Servers not using ' . $plugin->getName() . ' in the last 7 days are also omitted. </p>
        <table>
';

foreach ($plugin->getVersions() as $version)
{
    $count = $plugin->countServersUsingVersion($version);

    if ($count < 5)
    {
        continue;
    }

    echo '            <tr> <td>' . $version . '</td> <td>' . number_format($count) . '</td> </tr>
';
}
?>
        </table>
        <br/>
    </body>
</html>