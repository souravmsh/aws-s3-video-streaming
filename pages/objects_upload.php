<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\S3\S3Client;
 
//--------------------------------------------------------------
function get_mime_type($filename) 
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
//--------------------------------------------------------------
$video_dir = $config['upload_dir'];
$uploadOk  = 1;
$error     = "";
$info      = "";
$success   = "";

if(isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"]=='POST' && !empty($_FILES[
'file'])) {

    $file_name = basename($_FILES["file"]["name"]);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $new_file_name = rand(0,9); // get course id
    $new_file_name .= 'x'; // set a flag 'x' to detect the course id length
    $new_file_name .= rand(0,9); // get lesson id
    $new_file_name .= 'x'; // set a flag 'x' to detect the chapter id length
    $new_file_name .= rand(0,9); // get current user id
    $new_file_name .= 'x'; // set a flag 'x' to detect the user id length
    $new_file_name = uniqid($new_file_name).time().uniqid();
    $dir_path  = $video_dir .date('ymd'); 
    $file_path = $dir_path ."/". $new_file_name . ".".$file_extension; 

    if (empty($file_name))
    {
        $error = "You did not select any file!"; 
    }
    else if(!in_array($file_extension, $config['supported_ext'])) 
    {
        $error = "Sorry, only ".strtoupper(implode(' | ', $config['supported_ext']))." files are allowed.";
    } 
    else 
    { 
        ### Instantiate an Amazon S3 client.
        $s3 = new S3Client([
            'version'     => $config['version'],
            'region'      => $config['region'],
            'credentials' => [
                'key'    =>  $config['key'],
                'secret' =>  $config['secret'],
            ]
        ]);


        if ($config['clear_localstorage']) { 
            # Using operation methods creates a command implicitly
            $result = $s3->upload(
                $config['bucket'],
                $file_path, 
                fopen($_FILES["file"]["tmp_name"], 'rb'), 
                $config['upload_acl']
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
                $error = "Unable to create file directory";
            }
            else if (move_uploaded_file($_FILES["file"]["tmp_name"], $file_path)) 
            {
                $success = "The file ". $file_name. " has been uploaded to the local storage.";
                $success .= "<br/>Uploaded path {$file_path}";
                 
                # Using operation methods creates a command implicitly
                $result = $s3->putObject([
                    'Bucket'     => $config['bucket'],
                    'Key'        => $file_path,
                    'SourceFile' => $file_path,
                    'ContentType' => get_mime_type($file_path),
                    'ACL'   => $config['upload_acl'],
                ]);
            } 
            else 
            {
                $error = "Sorry, there was an error uploading your file.";
            }

        }

        if (!empty($result['ObjectURL']))
        {
            $info = "File stored to the S3 bucket successful!";
            $info .= "<br/>Uploaded S3 path {$result['ObjectURL']}";

            // unlink cache files
            if(file_exists('public/files/object.txt'))
                unlink('public/files/object.txt');
            if(file_exists('public/files/jobs.txt'))
                unlink('public/files/jobs.txt');
        }
        else 
        {
            $error = "Unable to upload to the S3 bucket!";
        }


    } 
}   
  
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">S3 Upload</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=objects_upload" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>


<div class="row">
  <div class="col-9">
    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane fade show active" id="v-pills-php" role="tabpanel" aria-labelledby="v-pills-php-tab">
            <?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
            <?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>
            <?= (!empty($info)?"<div class=\"alert alert-warning\">$info</div>":"") ?>

            <form action="?page=objects_upload" class="form-inline" method="post" enctype="multipart/form-data">
                <div class="input-group col-7 mb-2 mr-sm-2"> 
                    <input type="file" name="file" class="custom-file-input" id="file">
                    <label class="custom-file-label" for="file">Choose video file...</label>
                </div> 
                <button type="submit" class="btn btn-primary mb-2">Submit</button>
            </form>  
        </div>


        <!-- Upload with JavaScript -->
        <div class="tab-pane fade" id="v-pills-js" role="tabpanel" aria-labelledby="v-pills-js-tab"> 
            <div class="form-inline">
                <div class="input-group col-7 mb-2 mr-sm-2"> 
                    <input type="file"  class="custom-file-input" id="fileUpload">
                    <label class="custom-file-label" for="fileUpload">Choose video file...</label>
                </div>  
                <button type="submit" onclick="s3upload()" class="btn btn-primary">Upload</button>
            </div>

            <div class="progress mt-3">
              <progress class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" max="100" value="0" ></progress>
            </div>

            <results id="results"></results>
        </div>

    </div>
  </div>
  <div class="col-3">
    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
      <a class="nav-link active" id="v-pills-php-tab" data-toggle="pill" href="#v-pills-php" role="tab" aria-controls="v-pills-php" aria-selected="true">Upload with PHP</a>
      <a class="nav-link" id="v-pills-js-tab" data-toggle="pill" href="#v-pills-js" role="tab" aria-controls="v-pills-js" aria-selected="false">Upload with JavaScript</a>
    </div>
  </div>
</div>





<script src="https://sdk.amazonaws.com/js/aws-sdk-2.1.24.min.js"></script>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<link rel="stylesheet" href="https://rawgit.com/enyo/dropzone/master/dist/dropzone.css">
<script src="https://rawgit.com/enyo/dropzone/master/dist/dropzone.js"></script>
<script type="text/javascript">
//Bucket Configurations
AWS.config.update({
    accessKeyId : 'AKIASES75MS2FES5OYF5',
    secretAccessKey : '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'
});
AWS.config.region = 'us-east-1'; 

function s3upload() {
    var files   = document.getElementById('fileUpload').files; 
 
    if (files.length > 0) {
        var file = files[0];
        var fileName = file.name;
        var uploadPath     = 'public/web/';
        var filePath = uploadPath + fileName; 
 
        var s3 = new AWS.S3({
            params: {Bucket: 'videoinputskillmover'}
        });
        s3.putObject({
            Key : filePath,
            Body: file,
            ACL : 'public-read'
        }, function(err, data) {
            if (err) {
                $('results').text('ERROR: ' + err);
            } else {
                $('results').text('Successfully Uploaded!');
            }
        }).on('httpUploadProgress', function (progress) {
            var uploaded = parseInt((progress.loaded * 100) / progress.total);
            $("progress").attr('value', uploaded);
        });
    } else {
        $('results').text('Nothing to upload.');
    }
};
</script>


 



 