<?php
// Require the Composer autoloader.
require 'vendor/autoload.php';
require 'config/config.php';

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

$result  = [];
$success = "";
$error   = "";
 
$sesClient = new SesClient([
    'version' => $config['version'],
    'region'  => $config['region'],
    'credentials' => [ 
        'key'     => $config['key'],
        'secret'  => $config['secret'],
    ],
	'http'        => [
		'verify'  => false     
	]
]);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

	$recipients = []; 
	$subject = $message = "";
    
    if (empty($_POST["email"])) {
       $error .= "Email is required!<br/>";
    }else {
        $emails = explode(',', $_POST["email"]);
        foreach ($emails as $email) {
       		$email = filterInput($email);
	        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	          $error .= "The email {$email} is invalid!<br/>";
	        } else {
	        	array_push($recipients, $email);
	        }
       }
    }

    if (empty($_POST["subject"])) {
       $error .= "Subject is required!<br/>";
    }else {
       $subject = filterInput($_POST["subject"]);
    }
    
    if (empty($_POST["message"])) {
       $error .= "Message is required!<br/>";
    }else {
       $message = $_POST["message"];
    } 
 
    if (!empty($subject) && !empty($message) && is_array($recipients) && empty($error)) {
		try {
		    $result = $sesClient->sendEmail([
		        'Destination' => [
		            'ToAddresses'  => $recipients,
		        ],
		        'ReplyToAddresses' => [$config['mail']['sender_email']],
		        'Source'  => $config['mail']['sender_email'],
		        'Message' => [
		          'Body' => [
		              (!empty($config['mail']['mail_type'])?ucfirst($config['mail']['mail_type']):'Text') => [
		                  'Charset' => $config['mail']['charset'],
		                  'Data'    => $message,
		              ]
		          ],
		          'Subject' => [
		              'Charset' => $config['mail']['charset'],
		              'Data'    => $subject,
		          ],
		        ], 
		    ]); 

       		$success = "<strong>Email sent!</strong><br/>Message ID: {$result['MessageId']}"; 
		} catch (AwsException $e) {
		    $error .= $e->getMessage(). "<br/>";
		    $error .= "The email was not sent. Error message: ".$e->getAwsErrorMessage()."<br/>";
		}
    }
}
 
function filterInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Email</h1> 
    <div class="btn-toolbar mb-2 mb-md-0"> 
        <a href="?page=email" class="btn btn-sm btn-outline-success">
            <span data-feather="refresh-cw"></span>
            Refresh
        </a>
    </div>
</div>

<?= (!empty($error)?"<div class=\"alert alert-danger\">$error</div>":"") ?>
<?= (!empty($success)?"<div class=\"alert alert-success\">$success</div>":"") ?>

<form method="post" action="?page=email" class="needs-validation" novalidate>
	<div class="custom-control custom-switch">
		<input type="checkbox" class="custom-control-input" id="recipients">
		<label class="custom-control-label" for="recipients">Multiple Recipients</label>
	</div>
	<div class="form-group">
		<label for="email">Recipient</label>
		<input type="email" name="email" class="form-control" id="email" placeholder="eg:- info@example.com" aria-describedby="emailHelp" required>
		<small id="emailHelp" class="form-text text-muted">Enter the recipient email address</small>
	</div> 
	<div class="form-group">
		<label for="subject">Subject</label>
		<input type="subject" name="subject" class="form-control" id="subject" placeholder="Subject" required>
	</div> 
	<div class="form-group">
		<label for="message">Message</label>
		<textarea name="message" class="form-control" id="message" rows="3" placeholder="Message" required></textarea>
	</div>
	<button type="submit" class="btn btn-primary">Send</button>
</form>


<script type="text/javascript">
(function() {
	var checkbox = document.getElementById("recipients");
	checkbox.addEventListener('click', function(){
		console.log(checkbox.checked)
		if (checkbox.checked) { 
			document.querySelector('label[for="email"]').textContent = 'Recipient(s)';
			document.querySelector('input[id="email"]').type ='text';
			document.querySelector('input[id="email"]').placeholder ='eg:- info@example.com, help@example.com';
			document.querySelector('[id="emailHelp"]').innerHTML = 'You can add multiple recipients by using comma';
		} else {
			document.querySelector('label[for="email"]').textContent = 'Recipient';
			document.querySelector('input[id="email"]').type ='email';
			document.querySelector('input[id="email"]').placeholder ='eg:- info@example.com';
			document.querySelector('[id="emailHelp"]').innerHTML = 'Enter the recipient email address';
		}
	})

	window.addEventListener('load', function() {
		// Fetch all the forms we want to apply custom Bootstrap validation styles to
		var forms = document.getElementsByClassName('needs-validation');
		// Loop over them and prevent submission
		var validation = Array.prototype.filter.call(forms, function(form) {
			form.addEventListener('submit', function(event) {
				if (form.checkValidity() === false) {
					event.preventDefault();
					event.stopPropagation();
				}
				form.classList.add('was-validated');
			}, false);
		});
	}, false);
})(); 
</script>