<?php 
session_start();
require_once 'config/config.php';
if(empty($_SESSION['oauth_token']))
{
   header('location:index.php');
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
      <title><?= (!empty($_GET['page'])?ucwords(str_replace('_', ' ', $_GET['page'])):'Home') ?> :: <?= $config['application']['title'] ?></title>
      <!-- Favicons --> 
      <link rel="icon" href="<?= $config['application']['favicon'] ?>">
      <!-- Bootstrap core CSS -->
      <link href="public/css/bootstrap.min.css" rel="stylesheet">
      <!-- Custom styles for this template -->
      <link href="public/css/styles.css" rel="stylesheet">
      <!-- JQuery -->
      <script src="public/js/jquery.min.js"></script>
   </head>
   <body>
      <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
         <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="dashboard.php"><?= $config['application']['title'] ?></a>
         <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
         </button>
         <input class="form-control form-control-dark w-100" type="search" placeholder="Search" aria-label="Search">
         <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
               <a class="nav-link" href="index.php?page=logout">Sign out</a>
            </li>
         </ul>
      </nav>
      <div class="container-fluid">
         <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
               <div class="sidebar-sticky pt-3">
                  <ul class="nav flex-column">
                     <li class="nav-item border-bottom p-1 text-primary">
                         <div class="media">
                          <img src="<?= $_SESSION['oauth_user']['picture'] ?>" class="mr-1 rounded-circle" alt="picture" width="35" height="35">
                          <div class="media-body">
                            <h6 class="m-0"><?= $_SESSION['oauth_user']['name'] ?></h6>
                            <?= $_SESSION['oauth_user']['email'] ?>
                          </div>
                        </div>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                        <span data-feather="home"></span>
                        Dashboard <span class="sr-only">(current)</span>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=objects_upload">
                        <span data-feather="file"></span>
                        S3 Upload
                        </a>
                     </li> 
                     <li class="nav-item">
                        <a class="nav-link" href="?page=objects">
                        <span data-feather="shopping-cart"></span>
                        S3 Uploaded Objects
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=jobs_create">
                        <span data-feather="layers"></span>
                        Create Job
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=jobs">
                        <span data-feather="clock"></span>
                        List of Jobs by Pipeline
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=pipelines">
                        <span data-feather="activity"></span>
                        List of Pipelines
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=signed_url">
                        <span data-feather="cloud-lightning"></span>
                        CloudFront Signed URL
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=email">
                        <span data-feather="mail"></span>
                        Email
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="?page=reports">
                        <span data-feather="bar-chart-2"></span>
                        Reports
                        </a>
                     </li>
                  </ul>
                  <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                     <span>Saved reports</span>
                     <a class="d-flex align-items-center text-muted" href="#" aria-label="Add a new report">
                     <span data-feather="plus-circle"></span>
                     </a>
                  </h6>
                  <ul class="nav flex-column mb-2">
                     <li class="nav-item">
                        <a class="nav-link" href="#">
                        <span data-feather="file-text"></span>
                        This week
                        </a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="#">
                        <span data-feather="file-text"></span>
                        Current month
                        </a>
                     </li> 
                     <li class="nav-item">
                        <a class="nav-link" href="#">
                        <span data-feather="file-text"></span>
                        Year-end
                        </a>
                     </li>
                  </ul>
               </div>
            </nav>
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
               <?php
               $page = (!empty($_GET['page'])?$_GET['page']:null);
               if (file_exists("./pages/$page.php")) {
                  require_once "./pages/$page.php";
               } else {
                  require_once "./pages/dashboard.php";
               }
               ?>
            </main>
         </div>
      </div>
      <script src="public/js/popper.min.js"></script>
      <script src="public/js/bootstrap.min.js"></script>
      <script src="public/js/feather.min.js"></script>
      <script src="public/js/scripts.js"></script>
   </body>
</html>