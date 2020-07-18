<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\Exception\AwsException;
use Aws\ElasticTranscoder\ElasticTranscoderClient;

$result = [];
 
### ElasticTranscoderClient 
$elasticTranscoder = new ElasticTranscoderClient([
    'version' => $config['version'],
    'region'  => $config['region'],
    'credentials' => [ 
        'key'    => $config['key'],
        'secret' => $config['secret'],
    ]
]);


# pipeline list
$result = $elasticTranscoder->listPipelines([
    'Ascending' => 'false',
    // 'PageToken' => 'GET'
]); 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">List of Pipelines</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=pipelines" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>

<?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
<?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-sm">
        <thead class="table-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">ID/Pipeline ID</th>
                <th scope="col">Name</th>
                <th scope="col">Status</th> 
                <th scope="col">Input Bucket</th>
                <th scope="col">Output Bucket</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($result['Pipelines']) && is_array($result['Pipelines'])) { ?>
                <?php foreach($result['Pipelines'] as $key => $item) { ?>
                <tr>
                    <th><?= ($key+1) ?></th>
                    <td><a href="?page=jobs&pipeline_id=<?= $item['Id'] ?>"><?= $item['Id'] ?></a></td> 
                    <td><?= $item['Name'] ?></td>   
                    <td><?= ($item['Status']=='Active')?"<span class=\"badge badge-success\">{$item['Status']}</span>":"<span class=\"badge badge-danger\">{$item['Status']}</span>" ?></td> 
                    <td><?= $item['InputBucket'] ?></td>  
                    <td><?= $item['OutputBucket'] ?></td> 
                    <td> 
                        <a href="?page=jobs&pipeline_id=<?= $item['Id'] ?>">View Jobs</a>
                    </td> 
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
 