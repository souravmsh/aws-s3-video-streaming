<?php  
session_start();
require_once 'vendor/autoload.php';
require_once 'config/config.php';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_url']);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) 
{ 
	$_SESSION['oauth_token'] = $client->fetchAccessTokenWithAuthCode($_GET['code']);
	// now you can use this profile info to create account in your website and make user logged in.
}  

if(!empty($_GET['page']) && $_GET['page']=='logout') 
{
	// Remove token and user data from the session
	unset($_SESSION['oauth_token']);
	unset($_SESSION['user']);

	// Reset OAuth access token
	$client->revokeToken();

	// Destroy entire session data
	session_destroy();

	// Redirect to homepage
	header("location:index.php");
}

// authenticate code from Google OAuth Flow
if(!empty($_SESSION['oauth_token']))
{ 
	$client->setAccessToken($_SESSION['oauth_token']['access_token']); 

	// get profile info
	$oAuth = new Google_Service_Oauth2($client);
	$info  = $oAuth->userinfo->get();

	$_SESSION['oauth_user'] = [
		'name'    => $info->name,
		'email'   => $info->email,
		'picture' => $info->picture,
		'session' => date('Y-m-d H:i:s')
	];

	file_put_contents('public/files/log.txt', json_encode($_SESSION['oauth_user']).PHP_EOL, FILE_APPEND);

	header('location: dashboard.php');
} 
?>
<!doctype html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <meta name="description" content="">
      <meta name="author" content="">
      <meta name="generator" content="">
      <meta name="theme-color" content="#563d7c">
      <title>SignIn :: <?= $config['application']['title'] ?></title>
      <!-- Favicons --> 
      <link rel="icon" href="<?= $config['application']['favicon'] ?>">
      <!-- Bootstrap core CSS -->
      <link href="public/css/bootstrap.min.css" rel="stylesheet">
      <!-- Custom styles for this template -->
      <link href="public/css/styles.css" rel="stylesheet">  
   </head>
	<body class="login">
		<div class="container">
			<div class="row">
			  <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
			    <div class="card card-signin my-5">
			      <div class="card-body">
			        <h5 class="card-title text-center">Sign In</h5>
			        <form class="form-signin"> 
			          <hr class="my-4">
			          <a href="<?= $client->createAuthUrl() ?>" class="btn btn-lg btn-outline-danger btn-block text-uppercase">
						<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							 width="14px" height="14px" viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
							<path fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" d="
							M18.81,4.55c-3.24-3.04-8.32-3.36-12-0.92c-4.28,2.83-5.75,8.64-3.29,13.16c2.09,3.85,6.45,5.83,10.74,5.05
							c4.31-0.78,8.17-5.08,7.32-9.69h-9.33"/>
						</svg> Sign in with Google</a>
			        </form>
			      </div>
			    </div>
			  </div>
			</div>
		</div>
	</body>
</html>