<!DOCTYPE html>
<html lang="en">
    <head>
        <title>TODO supply a title</title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
        <!-- Favicons -->
        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
        
        <link rel="shortcut icon" href="../assets/ico/favicon.ico">
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <script src="http://api-maps.yandex.ru/2.0-stable/?load=package.full&lang=ru-RU" type="text/javascript"></script>
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    </head>
    <body>
        <div class="container bs-docs-container">
            <div class="row">
                <div class="">
                    <form role="form" action="upload.php?do=upload" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="exampleInputFile">Выберите файл</label>
                            <input type="file" name="file" id="exampleInputFile">
                        </div>
                        <button type="submit" class="btn btn-default">Загрузить</button>
                    </form>
                </div>
            </div>
        </div>
        
    </body>
</html>