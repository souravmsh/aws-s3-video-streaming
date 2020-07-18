<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\S3\S3Client;

$result = [];
  
$s3Client = new S3Client([
    'version'     => $config['version'],
    'region'      => $config['region'],
    'credentials' => [
        'key'    =>  $config['key'],
        'secret' =>  $config['secret'],
    ]
]);


# Clear Caching  
$filename = 'public/files/objects.txt';
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


# Delete Object
if (!empty($_GET['delete'])) 
{
    $delete = $s3Client->deleteObject([
        'Bucket' => $config['bucket'],
        'Key'    => $_GET['delete']
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


# Read Cache 
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
   

# Show all object list
if ($differenceInSeconds >= $config['expires'] || $differenceInSeconds == 0) 
{
    $result = $s3Client->ListObjects([
        'Bucket' => (!empty($_GET['bucket'])?$_GET['bucket']:$config['bucket']), 
    ]);
    // $promise = $result->each(function ($item) {
    //     echo 'Got ' . var_export($item, true) . "\n";
    // });

    // Create Cache
    if(is_array($result) || is_object($result))
    {
        $content = var_export($result, true);
        $content = str_replace('Aws\Result::__set_state', '', $content);
        $content = str_replace('Aws\Api\DateTimeResult::__set_state', '', $content);
        file_put_contents($filename,  '<?php return ' . $content . ';');
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Uploaded Objects (Buckets: <a href="?page=objects&bucket=<?= $config['bucket'] ?>">input_buckets</a>, <a href="?page=objects&bucket=<?= $config['output_bucket'] ?>">output_bucket</a>)</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=objects&refresh=force" class="btn btn-sm btn-outline-success">
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
                <th scope="col">Objects</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($result['Contents']) && is_array($result['Contents'])) { ?>
                <?php foreach($result['Contents'] as $key => $item) { ?> 
                <tr>
                    <th scope="row"><?= ($key+1) ?></th>
                    <td>
                        <ul> 
                            <li>File: <?= basename($item['Key']) ?></li>
                            <li>Direct:   <a href="<?= (str_replace('://s3.', '://'.$config['bucket'].'.s3.', $config['s3_url']).'/'.$item['Key']) ?>" target="_blank">Link</a></li>
                            <li>CloudFront:   <a href="<?= $config['cloudfront'].'/'.$item['Key'] ?>" target="_blank">Link</a></li>
                            <li>Size: <?= $item['Size'] ?></li> 
                            <li>Last Modified: <?= (!empty($item['LastModified'])?(date("F j, Y h:i:s A", strtotime(((array)$item['LastModified'])['date']))):'') ?></li>
                            <li>Owner Name: <?= $item['Owner']['DisplayName'] ?></li>
                            <li>OwnerID:   <?= $item['Owner']['ID'] ?></li>
                        </ul>
                    </td> 
                    <td> 
                        <a class="btn btn-sm btn-outline-info mt-2" href="?page=jobs_create&video_key=<?= $item['Key'] ?>">Create Job</a>
                        <br/>
                        <a class="btn btn-sm btn-outline-danger mt-2" href="?page=objects&delete=<?= $item['Key'] ?>" onclick="return confirm('Are you sure?')">Delete Object</a>
                    </td> 
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
</div>
