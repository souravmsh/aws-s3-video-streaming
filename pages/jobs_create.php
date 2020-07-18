<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\Exception\AwsException;
use Aws\ElasticTranscoder\ElasticTranscoderClient;

$result  = [];
$success = "";
$error   = "";
# Video ID videoKey 
$videoKey = (!empty($_GET['video_key'])?$_GET['video_key']:'');

if (!empty($videoKey))
{
	$info = pathinfo($videoKey);
	$fileName = basename($videoKey);
	$videoId  =  basename($videoKey,'.'.$info['extension']);
	$OutputKeyPrefix  = dirname($videoKey)."/".$videoId."/";

	 
	### ElasticTranscoderClient 
	$elasticTranscoder = new ElasticTranscoderClient([
	    'version' => $config['version'],
	    'region'  => $config['region'],
	    'credentials' => [ 
	        'key'    => $config['key'],
	        'secret' => $config['secret'],
	    ]
	]);


	// Create the job.  
	try { 
		$result = $elasticTranscoder->createJob([
		    'PipelineId' => $config['pipeline_id'],
		    'OutputKeyPrefix' => $OutputKeyPrefix,
		    'Input' => [
		        'Key'         => $videoKey,
		        'FrameRate'   => 'auto',
		        'Resolution'  => 'auto',
		        'AspectRatio' => 'auto',
		        'Interlaced'  => 'auto',
		        'Container'   => 'auto',
		    ],
		    'Outputs' => [
		        [
		            'PresetId'  => $config['preset_id'],
		            'Key'       => $config['output_key'],
		            'Rotate'    => 'auto',
		            'SegmentDuration' => '10',
		        ],
		    ],
		    'Playlists' => [
		        [ 
		            'Name'       => $config['playlist_name'],
		            'Format'     => 'HLSv3',
		            'OutputKeys' => [
		            	$config['output_key']
		            ],
		        ],
		    ],
		]);  

		$job = $result->get('Job');
		if (!empty($job['Id'])) 
		{
			$success = "The job {$job['Id']} has been created successful!";
		}
		else
		{
			$error = "Unable to create the job!";
		}
		
	} catch (AwsException $e) { 
		$error = $e->getMessage() . "\n";
	} 
}
else
{
	$error = "The videoKey is required!";
}
 

?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create Job(s) by Video Key</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=jobs_create" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>

<?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
<?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>

<form class="form-inline" method="get"> 
	<input name="page" value="jobs_create" type="hidden">
	<div class="input-group col-7 mb-2 mr-sm-2"> 
	<input type="text" name="video_key" class="form-control" placeholder="Video ID/Key (Actual path of the s3 file)" value="<?= $videoKey ?>">
	</div> 
	<button type="submit" class="btn btn-primary mb-2">Submit</button>
</form> 


 