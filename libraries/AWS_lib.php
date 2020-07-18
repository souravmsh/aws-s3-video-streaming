<?php defined('BASEPATH') OR exit('No direct script access allowed');
		
use Aws\S3\S3Client; 
use Aws\Exception\AwsException;
use Aws\CloudFront\CloudFrontClient; 
use Aws\ElasticTranscoder\ElasticTranscoderClient;

/*
| HOW TO USE
-----------------------------------------------------------------
	# load library
	$this->load->library('AWS_lib');
-----------------------------------------------------------------
	# upload file to local and s3 bucket
	$upload = $this->aws_lib->upload($_FILES['file']);
	var_dump($upload); // return status, message, data
-----------------------------------------------------------------
	# create aws job
	if ($upload['status'])
	{
		$job = $this->aws_lib->job($upload['data']['key']);
		var_dump($job); 
	}
-----------------------------------------------------------------
	# get signed url
	if ($job['status'])
	{
 		$signed_url = $this->aws_lib->signed_url($job['data']['key']);
 		var_dump($signed_url)
	}
-----------------------------------------------------------------
	# ALL IN ONE PROCESS
	$signed = $this->aws_lib->process($_FILES['file']);
	$signed = $this->aws_lib->process($_FILES['file'], "200718-mytestingfile");
	var_dump($signed)
-----------------------------------------------------------------
*/

class AWS_lib
{
	public $key;
	public $source_url;

	public function __construct()
	{
		// load codeigniter config
		// $this->ci  =& get_instance();
		// $this->aws = (object)$this->ci->load->config('aws', true);
		$this->aws = include '../config/config.php';
	}

	// perfor all action
	public function process($reqFiles, $fileId = null, $policyAccept = true)
	{
		$upload = $this->upload($reqFiles, $fileId);
		if ($upload['status'] == 'true')
		{
			$job = $this->job($upload['data']['key']);
			if ($job['status'] == 'true')
			{
				return $this->signed_url($job['data']['key'], $policyAccept);
			}
			return $job;  
		}
		return $upload; 
	}

	public function upload($reqFiles = [], $fileId = null)
	{
		$video_dir = $this->aws->upload_dir;
		$status    = 'false';
		$message   = "<ul>";
		$data      = [];

		if(!empty($reqFiles)) 
		{
			if (!empty($fileId))
			{ 
			    $extension = strtolower(pathinfo($reqFiles['name'], PATHINFO_EXTENSION));
				$file_parts = explode("-", $fileId);
				$dir_path  = $file_parts[0];
			    $file_name = basename(!empty($file_parts[1])?$file_parts[1]:$file_parts[0]);
			    $file_path = $video_dir."".$dir_path ."/". $file_name . "." . $extension; 
			}
			else
			{
			    $file_name = basename($reqFiles["name"]);
			    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

			    $new_file_name = date('H'); // get course id
			    $new_file_name .= 'x'; // set a flag 'x' to detect the course id length
			    $new_file_name .= date('i'); // get lesson id
			    $new_file_name .= 'x'; // set a flag 'x' to detect the chapter id length
			    $new_file_name .= date('s'); // get current user id
			    $new_file_name .= 'x'; // set a flag 'x' to detect the user id length
			    $new_file_name = uniqid($new_file_name).time().uniqid();
			    $dir_path  = $video_dir .date('ymd'); 
			    $file_path = $dir_path ."/". $new_file_name . ".".$extension;
			}
 
		    if (empty($file_name))
		    {
		        $message .= "<li>Invalid file!</li>"; 
		        $status = 'false';
		    }
		    else if(!in_array($extension, $this->aws->supported_ext)) 
		    {
		        $message .= "<li>Sorry, only ".strtoupper(implode(' | ', $this->aws->supported_ext))." files are allowed!</li>";
		        $status = 'false';
		    } 
		    else 
		    { 
		        ### Instantiate an Amazon S3 client.
		        $s3 = new S3Client([
		            'version'     => $this->aws->version,
		            'region'      => $this->aws->region,
		            'credentials' => [
		                'key'    =>  $this->aws->key,
		                'secret' =>  $this->aws->secret,
		            ]
		        ]);
 
		        if ($this->aws->clear_localstorage) 
		        { 
		            # Using operation methods creates a command implicitly
		            $result = $s3->upload(
		                $this->aws->bucket,
		                $file_path, 
		                fopen($_FILES["file"]["tmp_name"], 'rb'), 
		                $this->aws->upload_acl
		            );
		        } 
		        else
		        {
			        if(is_dir($dir_path) === false)
			        {
			            mkdir($dir_path, 0777, true);
			        } 

			        if (!is_dir($dir_path))
			        {
			            $message .= "<li>Unable to create file directory</li>";
			        	$status = 'false';
			        }
			        else if (move_uploaded_file($reqFiles["tmp_name"], $file_path))
			        {
			            $message .= "<li>The file ". $file_name. " has been uploaded to local storage.</li>";
			            $message .= "<li>Uploaded path {$file_path}</li>";
			             
			            # Using operation methods creates a command implicitly
			            $result = $s3->putObject([
			                'Bucket'      => $this->aws->bucket,
			                'Key'         => $file_path,
			                'SourceFile'  => $file_path,
			                'ContentType' => $this->mime_type($file_path),
			                'ACL'         => $this->aws->upload_acl,
			            ]); 
			        } 
			        else 
			        {
			            $message .= "<li>Sorry, there was an error uploading your file.</li>";
		        		$status = 'false';
			        }
		    	}


	            if (!empty($result['ObjectURL']))
	            {
	                $message .= "<li>File stored to the S3 bucket successful!</li>";
	                $data   = [
	                	'key' => $file_path,
	                	'url' => $result['ObjectURL']
	                ]; 
	        		$status = 'true'; 
	        		$this->key = $file_path;
	        		$this->source_url = $result['ObjectURL'];
	            }
	            else 
	            {
	                $message .= "<li>Unable to upload to the S3 bucket!</li>"; 
	        		$status = 'false';
	            } 
		    } 
		}
		else
		{
			$message .= "<li>You did not select any file!</li>";
    		$status = 'false';
		}  
		$message .= "</ul>"; 

		return [
			'method'  => 'upload',
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
		];

	}

	public function job($videoKey = null)
	{
		$result  = [];
		$status    = 'false';
		$message = "";
		$data    = [];

		if (!empty($videoKey))
		{
			$videoKey = str_replace('./', '', $videoKey);
			$info = pathinfo($videoKey);
			$fileName = basename($videoKey);
			$videoId  =  basename($videoKey,'.'.$info['extension']);
			$OutputKeyPrefix  = dirname($videoKey)."/".$videoId."/";
			 
			### ElasticTranscoderClient 
			$elasticTranscoder = new ElasticTranscoderClient([
			    'version' => $this->aws->version,
			    'region'  => $this->aws->region,
			    'credentials' => [ 
			        'key'    => $this->aws->key,
			        'secret' => $this->aws->secret,
			    ]
			]);

			// Create the job.  
			try { 
				$result = $elasticTranscoder->createJob([
				    'PipelineId' => $this->aws->pipeline_id,
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
				            'PresetId'  => $this->aws->preset_id,
				            'Key'       => $this->aws->output_key,
				            'Rotate'    => 'auto',
				            'SegmentDuration' => '10',
				        ],
				    ],
				    'Playlists' => [
				        [ 
				            'Name'       => $this->aws->playlist_name,
				            'Format'     => 'HLSv3',
				            'OutputKeys' => [
				            	$this->aws->output_key
				            ],
				        ],
				    ],
				]);  

				$job = $result->get('Job');
				if (!empty($job['Id'])) 
				{
					$status = 'true';
					$message .= "The job {$job['Id']} has been created successful!";
					$data = [
						'id'  => $job['Id'],
						'key' => $job['OutputKeyPrefix'].$this->aws->playlist_name.'.m3u8',
						'url' => $this->aws->cloudfront.'/'.$job['OutputKeyPrefix'].$this->aws->playlist_name.'.m3u8'
					];
				}
				else
				{
					$status = 'false';
					$message .= "Unable to create the job!";
				}
				
			} catch (AwsException $e) { 
				$status = 'false';
				$message .= $e->getMessage() . "\n";
			} 
		}
		else
		{
			$status = 'false';
			$message .= "The video key is required!";
		}

		return [
			'method'  => 'job',
			'status'  => $status,
			'message' => $message,
			'data'    => $data
		];
	}

	public function signed_url($videoKey = null, $policyAccept = true)
	{ 
		# All Available Variables
		$status    = 'false';
		$signed_url  = ""; 
		$message     = "";
		$expires     = time() + 600; // (10 minutes * 60 seconds) seconds
		$resourceKey = (filter_var($videoKey, FILTER_VALIDATE_URL)?$videoKey:($this->aws->cloudfront."/".str_replace('./', '', $videoKey)));

		if (!empty($videoKey))
		{

			# CloudFrontClient URL  
			$cloudFrontClient = new CloudFrontClient([
				'profile' => 'default',
			    'version' => $this->aws->version,
			    'region'  => $this->aws->region 
			]);

			try 
			{ 
				// WITH CUSTOM POLICY
				//----------------------------------------------------
				if ($policyAccept)
				{
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

				    $result = $cloudFrontClient->getSignedCookie([
				        'policy'      => $customPolicy,
				        'private_key' => $this->aws->private_key,
				        'key_pair_id' => $this->aws->key_pair_id
				    ]);
 
					$host_parts = array_reverse
					(
					    explode('.', parse_url($resourceKey, PHP_URL_HOST))
					);
					$cookie_domain = sprintf('.%s.%s', $host_parts[ 1 ], $host_parts[ 0 ]);
					$cookie_path = parse_url($resourceKey, PHP_URL_PATH);
					foreach ($result as $cookie_name => $cookie_value) {
    					setcookie($cookie_name, $cookie_value, $expires, "/");
					    // setcookie($cookie_name, $cookie_value, time() + (86400 * 30), $cookie_path, $cookie_domain);
					} 

					# SIGNED URL WITH POLICY
					#--------------------------------------------
					$signed_url = $cloudFrontClient->getSignedUrl([
				        'url'         => $resourceKey,
				        'policy'      => $customPolicy,
				        'private_key' => $this->aws->private_key,
				        'key_pair_id' => $this->aws->key_pair_id
					]);
				}
				else
				{
					# SIGNED URL
					#--------------------------------------------
					$signed_url = $cloudFrontClient->getSignedUrl([
						'url'         => $resourceKey,
					    'expires'     => $expires,
					    'private_key' => $this->aws->private_key,
					    'key_pair_id' => $this->aws->key_pair_id
					]);
				}

				if ($signed_url)
				{
					$status = 'true';
					$message .= "Signed url generated successful!";
					$data = [
						'policy'     => $policyAccept,
						'key'        => $this->key,
						'source_url' => $this->source_url,
						'url'        => $signed_url,
					];
				}
				else
				{
					$status = 'false';
					$message .= "Internal server error!";
				} 

			} catch (AwsException $e) { 
				$status = 'false';
				$message .= $e->getMessage() . "\n";
			} 
		}
		else
		{
			$status = 'false';
			$message .= "The video key is required!";
		}

		return [
			'method'  => 'signed_url',
			'status'  => $status,
			'message' => $message,
			'data'    => $data
		];
	}

	protected function mime_type($filename) 
	{
	    $idx = explode( '.', $filename );
	    $count_explode = count($idx);
	    $idx = strtolower($idx[$count_explode-1]);

	    $mimet = array( 
	        'txt' => 'text/plain',
	        'htm' => 'text/html',
	        'html' => 'text/html',
	        'php' => 'text/html',
	        'css' => 'text/css',
	        'js' => 'application/javascript',
	        'json' => 'application/json',
	        'xml' => 'application/xml',
	        'swf' => 'application/x-shockwave-flash',
	        'flv' => 'video/x-flv', 

	        // audio/video
	        'mp3' => 'audio/mpeg',
	        'qt' => 'video/quicktime',
	        'mov' => 'video/quicktime', 
	    );

	    if (isset( $mimet[$idx] )) {
	     return $mimet[$idx];
	    } else {
	     return 'application/octet-stream';
	    }
	}
}
