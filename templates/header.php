<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php
         foreach ( $data['menu'] as $link => $menuItem ) {
        	if (!empty($_SERVER['QUERY_STRING']) && strpos($link, $_SERVER['QUERY_STRING'])) echo $menuItem;
        }	?></title>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

        <!-- Optional theme -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

        <script src="http://yandex.st/jquery/2.1.0/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
<body>
    <div class="navbar navbar-inverse" role="navigation">
      <div class="container">
        <div class="navbar-header">
            
            <a class="navbar-brand" href="/">Фотообработка</a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <?php foreach ( $data['menu'] as $link => $menuItem ):?>
            <li class="<?php if (!empty($_SERVER['QUERY_STRING']) && strpos($link, $_SERVER['QUERY_STRING'])) echo 'active';?>"><a href="<?php echo $link;?>"><?php echo $menuItem;?></a></li>
            <?php endforeach;?>
            <!--<li class="active"><a href="#">Home</a></li>-->

          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>
    <div class="container">