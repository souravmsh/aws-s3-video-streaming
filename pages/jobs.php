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
$PipelineId = !empty($_GET['pipeline_id'])?$_GET['pipeline_id']:$config['pipeline_id'];

# Clear Cache 
$filename = 'public/files/jobs.txt';
$success             = "";
$error               = "";
$cacheDuration       = "";
$differenceInSeconds = 0;
if (!empty($_GET['refresh']) && $_GET['refresh']=='force') 
{
    if (file_exists($filename)) {
        unlink($filename);
    }
}


// Delete Jobs
if (!empty($_GET['delete'])) 
{
    $delete = $elasticTranscoder->cancelJob([
        'Id' => $_GET['delete'], // JOB ID REQUIRED
    ]);

    if ($delete)
    {
        $success =  $_GET['delete'] . ' was deleted or does not exist.' . PHP_EOL;
        if (file_exists($filename)) {
            unlink($filename);
        }
    } else {
        $error = ('Error: ' . $_GET['delete'] . ' was not deleted.' . PHP_EOL);
    }
}


// Read Cache
if (file_exists($filename)) {
    $timeFirst   = filemtime($filename);
    $timeSecond  = strtotime(date("Y-m-d H:i:s"));
    $differenceInSeconds = round(($timeSecond - $timeFirst)/60);
    $cacheDuration = "The cached file $filename was last modified: " . date ("F d Y H:i:s.", $timeFirst). " Duration ({$differenceInSeconds} seconds ago)"; 

    $result = include $filename;
    $result = $result['data'];

    if(!is_array($result))
    {
        $differenceInSeconds = 0;
    }
}  
 
# Job list API
if ($differenceInSeconds >= $config['expires'] || $differenceInSeconds == 0) 
{
    $result = $elasticTranscoder->listJobsByPipeline([
        // 'Ascending'  => 'true',
        // 'PageToken'  => 'GET',
        'PipelineId' => $PipelineId, // REQUIRED
    ]); 

    // $result = $elasticTranscoder->listJobsByStatus([
    //     'Ascending' => 'true',
    //     'Status'    => 'Complete', // Submitted, Progressing, Complete, Canceled, or Error.
    // ]);

    // Create Cache
    if(is_array($result) || is_object($result))
    {
        $content = var_export($result, true);
        $content = str_replace('Aws\Result::__set_state', '', $content);
        file_put_contents($filename,  '<?php return ' . $content . ';');
    }
} 
?>  

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">List of Jobs by Pipeline (<a href="?page=jobs&pipeline_id=<?= $PipelineId ?>"><?= $PipelineId ?></a>) </h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=jobs&refresh=force" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>

<div class="pb-3"><?= $cacheDuration ?></div>
<?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
<?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-sm">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">ID</th>
                <th scope="col">Status</th>
                <th scope="col">Size & Duration</th>
                <th scope="col">Input & Output</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($result['Jobs']) && is_array($result['Jobs'])) { ?>
                <?php foreach($result['Jobs'] as $key => $item) { ?>
                <tr>
                    <th scope="row"><?= ($key+1) ?></th>
                    <td>
                        <ul> 
                            <li>ID: <?= basename($item['Id']) ?></li>
                            <li>PresetId: <?= $item['Output']['PresetId'] ?></li> 
                        </ul>
                    </td> 
                    <td><?= ($item['Status']=='Complete')?"<span class=\"badge badge-success\">{$item['Status']}</span>":"<span class=\"badge badge-danger\">{$item['Status']}</span>" ?></td> 
                    <td>
                        <ul>  
                            <li>Size: <?= (!empty($item['Output']['FileSize'])?$item['Output']['FileSize']:'0') ?></li>
                            <li>Duration: <?= (!empty($item['Output']['Duration'])?$item['Output']['Duration']:'0') ?> (<?= (!empty($item['Output']['SegmentDuration '])?$item['Output']['SegmentDuration ']:'0') ?>)</li> 
                            <li>Date: <?= (!empty($item['Timing']['SubmitTimeMillis'])?(date("F j, Y h:i:s A", ceil($item['Timing']['SubmitTimeMillis'] / 1000))):'') ?></li>
                        </ul>
                    </td> 
                    <td>
                        <strong>Input</strong>
                        <ul> 
                            <li>File: <?= basename($item['Input']['Key']) ?></li>
                            <li>Direct:   <a href="<?= (str_replace('://s3.', '://'.$config['bucket'].'.s3.', $config['s3_url']).'/'.$item['Input']['Key']) ?>" target="_blank">Link</a></li>
                            <li>CloudFront:   <a href="<?= $config['cloudfront'].'/'.$item['Input']['Key'] ?>" target="_blank">Link</a></li>
                        </ul>
                        <strong>Output</strong>
                        <ul> 
                            <li>CloudFront HLS: <a href="<?= $config['cloudfront'].'/'.$item['OutputKeyPrefix'].$config['playlist_name'].'.m3u8' ?>" target="_blank">Link</a></li>
                            <li style="word-wrap: break-word;">Key: <?= $item['OutputKeyPrefix'].$config['playlist_name'].'.m3u8'  ?></li> 
                        </ul>
                    </td> 
                    <td width="100"> 
                        <?php if ($item['Status'] == 'Complete') { ?>
                        <a class="btn btn-sm btn-outline-success mt-2" href="?page=signed_url&video_key=<?= $item['OutputKeyPrefix'].$config['playlist_name'].'.m3u8' ?>">Signed Url</a>
                        <br/>
                        <?php } ?>
                        <?php if ($item['Status'] == 'Progressing') { ?>
                        <a class="btn btn-sm btn-outline-danger mt-2" href="?page=jobs&delete=<?= $item['Id'] ?>" onclick="return confirm('Are you sure?')">Delete Job</a>
                        <?php } ?>
                    </td> 
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
 