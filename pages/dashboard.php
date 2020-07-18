<?php
$rawData = "";
$objects = [];
$jobs    = [];
$result  = [];
$object_file = "public/files/objects.txt";
$job_file    = "public/files/jobs.txt";
if (file_exists($object_file)) 
{
    $rawData = include ($object_file);
    if (is_array($rawData)) 
    { 
        $objects = [];
        foreach ($rawData['data']['Contents'] as $item) 
        {
            $date = (!empty($item['LastModified'])?(date("j F Y", strtotime(((array)$item['LastModified'])['date']))):''); 

            if (date('F') == date('F', strtotime($date)))
            {
                $objects[] = $date; 
            }
        }
        $objects = array_count_values($objects);
    }
} 

// jobs
if (file_exists($job_file)) 
{
    $rawData = include ($job_file);
    if (is_array($rawData)) 
    { 
        $jobs = [];
        foreach ($rawData['data']['Jobs'] as $item) 
        {
            $date = (!empty($item['Timing']['SubmitTimeMillis'])?(date("j F Y", ceil($item['Timing']['SubmitTimeMillis'] / 1000))):''); 

            if (date('F') == date('F', strtotime($date)))
            {
                $jobs[] = $date; 
            }
        }
        $jobs = array_count_values($jobs);
    }
} 

for($i = ((!empty($_GET['report']) && ($_GET['report'] == 'Last week'))?7:30); $i >= 0 ; $i--)
{
    $date = date('j F Y', strtotime("-$i day"));
    $result[$date]['objects'] = (array_key_exists($date, $objects)?$objects[$date]:0);
    $result[$date]['jobs']    = (array_key_exists($date, $jobs)?$jobs[$date]:0);
}  
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="export">
                <span data-feather="download"></span>
                Export
            </button>
            <a href="?page=dashboard" class="btn btn-sm btn-outline-success">
                <span data-feather="refresh-cw"></span>
                Refresh
            </a>
        </div>

        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <span data-feather="calendar"></span> <?= (!empty($_GET['report'])?$_GET['report']:'Last month') ?></button>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="?page=dashboard&report=Last month" >Last month</a>
          <a class="dropdown-item" href="?page=dashboard&report=Last week" >Last week</a>
        </div>
    </div>
</div>
<canvas class="my-4 w-100" id="chart" width="900" height="380"></canvas>

 

<script src="public/js/Chart.min.js"></script>
<script type="text/javascript">
$(function(){
  // Graphs
  var ctx = document.getElementById('chart')
  var chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [
        <?php
            foreach ($result as $date => $value) {
                echo "'$date', ";
            }
        ?> 
      ], 
      datasets: [{
        label: 'Objects',
        fill: false,
        borderColor    : '#007bff',
        pointBackgroundColor: '#007bff',
        data: [
            <?php
                foreach ($result as $date => $value) {
                    echo "'{$value['objects']}', ";
                }
            ?> 
        ]
      },{
        label: 'Jobs',
        fill: false,
        backgroundColor: '#f67019',
        borderColor    : '#f67019',
        data: [
            <?php
                foreach ($result as $date => $value) {
                    echo "'{$value['jobs']}', ";
                }
            ?> 
        ]
      }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: 'Uploaded Objects & Jobs'
        },
        tooltips: {
            mode: 'index',
            intersect: false,
        },
        hover: {
            mode: 'nearest',
            intersect: true
        },
        scales: {
            xAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Date'
                }
            }],
            yAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'Value'
                }
            }]
        },
        bezierCurve : false,
        animation: {
            onComplete: function(){
                ctx.setAttribute('base64', chart.toBase64Image());
            }
        },
    }
  });

  // export image
  $("#export").on('click', function(){
    var a = document.createElement("a"); //Create <a>
    a.href = $('#chart').attr('base64'); //Image Base64 Goes here
    a.download = "Chart.jpg"; //File name Here
    a.click(); //Downloaded file
  }); 
 
})
</script>