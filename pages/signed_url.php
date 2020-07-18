<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\CloudFront\CloudFrontClient; 
use Aws\Exception\AwsException;
use Aws\ElasticTranscoder\ElasticTranscoderClient;


# All Available Variables
$signed_url  = ""; 
$success     = "";
$error       = ""; 
$videoKey    = (!empty($_GET['video_key'])?$_GET['video_key']:'');
$policyAccept = (!empty($_GET['policy'])?$_GET['policy']:'');
$resourceKey = "{$config['cloudfront']}/{$videoKey}";
$expires     = time() + 300; // 5 minutes (5 * 60 seconds) from now.
$customPolicy = <<<POLICY
{
    "Statement": [
        {
            "Resource": "{$resourceKey}",
            "Condition": {
                "IpAddress": {"AWS:SourceIp": "{$_SERVER['REMOTE_ADDR']}/32"},
                "DateLessThan": {"AWS:EpochTime": {$expires}}
            }
        }
    ]
}
POLICY;

if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' && !empty($videoKey))
{
	# CloudFrontClient URL  
	$cloudFrontClient = new CloudFrontClient([
		'profile' => 'default',
	    'version' => $config['version'],
	    'region'  => $config['region'] 
	]);

	try 
	{ 
		// WITH CUSTOM POLICY
		//----------------------------------------------------
		if ($policyAccept)
		{
		    $result = $cloudFrontClient->getSignedCookie([
		        'policy'      => $customPolicy,
		        'private_key' => $config['private_key'],
		        'key_pair_id' => $config['key_pair_id']
		    ]);

		    /* If successful, returns something like:
			CloudFront-Policy = eyJTdGF0...fX19XX0_
			CloudFront-Signature = RowqEQWZ...N8vetw__
			CloudFront-Key-Pair-Id = AAPKAJIKZATYYYEXAMPLE
			*/
			$host_parts = array_reverse
			(
			    explode('.', parse_url($resourceKey, PHP_URL_HOST))
			);
			$cookie_domain = sprintf('.%s.%s', $host_parts[ 1 ], $host_parts[ 0 ]);
			$cookie_path = parse_url($resourceKey, PHP_URL_PATH);
			foreach ($result as $cookie_name => $cookie_value) {
				// setcookie($cookie_name, $cookie_value, $expires, "/");
			    // setcookie($cookie_name, $cookie_value, time() + (86400 * 30), $cookie_path, $cookie_domain);
			}


			# SIGNED URL WITH POLICY
			#--------------------------------------------
			$signed_url = $cloudFrontClient->getSignedUrl([
		        'url'         => $resourceKey,
		        'policy'      => $customPolicy,
		        'private_key' => $config['private_key'],
		        'key_pair_id' => $config['key_pair_id']
			]);
		}
		else
		{
			# SIGNED URL
			#--------------------------------------------
			$signed_url = $cloudFrontClient->getSignedUrl([
				'url'         => $resourceKey,
			    'expires'     => $expires,
			    'private_key' => $config['private_key'],
			    'key_pair_id' => $config['key_pair_id']
			]);
		}

		if ($signed_url)
		{
			$success = "Signed url generated successful!";
		}
		else
		{
			$error = "Internal error!";
		} 

	} catch (AwsException $e) { 
		$error = $e->getMessage() . "\n";
	} 
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">CloudFront Signed URL by Video Key</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=signed_url" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>

<?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
<?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>

<div class="row">
	<div class="col-7 mb-3 pb-3"> 
		<form class="card card-body mb-3" method="get"> 
			<input type="hidden" name="page" value="signed_url">
			<div class="form-group">
				<label for="video_key">Video key</label>
				<input type="text" name="video_key" id="video_key" class="form-control" placeholder="Video ID/Key (Actual path of the s3 file)" value="<?= $videoKey ?>">
			</div>
			<div class="form-group form-check">
				<input type="checkbox" name="policy" value="accept" class="form-check-input" id="policy" <?= (!empty($_GET['policy'])?'checked':'') ?>>
				<label class="form-check-label" for="policy">Accept Custom Policy</label>
			</div>
			<button type="submit" class="btn btn-primary mb-2">Submit</button>
		</form>

		<div class="card">
			<video class="card-body" id="video" controls>The canned policy video will be here.</video>
		</div>
	</div>
	<div class="col mb-3">
		<div class="card">
			<div class="card-header text-white bg-primary">Output</div>
			<textarea class="card-body border-primary " rows="13" placeholder="Signed URL" data-toggle="popover" data-content="Click to Copy"><?= $signed_url ?></textarea>
		</div>  
	</div>
</div>
 
<script type="text/javascript" src="http://jwpsrv.com/library/4+R8PsscEeO69iIACooLPQ.js"></script>
<script type="text/javascript">
jwplayer("video").setup({
    autostart: true,
    file: "<?= $signed_url ?>", 
    width: "100%",
    height: "300",
    primary: "html",
});

$("textarea").click(function(){
    $(this).select();
    document.execCommand('copy');
    $(this).attr('data-content', 'Copied!');
	$(this).popover('show');
});
</script> 