<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once 'function.php';

print_r(exif_read_data('2014-02-22 11.09.33 (1).jpg'));
exit;
$filelist = array();
$folder = dirname(__FILE__) . "/img/";
if ($handle = opendir($folder)) {
    while ($entry = readdir($handle)) {
        if (is_file($folder . $entry)) {
            $filelist[] = $folder . $entry;
        }
        
    }
    closedir($handle);
}


foreach ($filelist as $item) {
    $imgPath = $item;
    if (exif_imagetype($imgPath)) {
        $exif = exif_read_data($imgPath);
        //print_r($exif);

        // Широта
        $latitude['degrees'] = getCoord( $exif['GPSLatitude'][0] );
        $latitude['minutes'] = getCoord( $exif['GPSLatitude'][1] );
        $latitude['seconds'] = getCoord( $exif['GPSLatitude'][2] );

        $latitude['minutes'] += 60 * ($latitude['degrees'] - floor($latitude['degrees']));
        $latitude['degrees'] = floor($latitude['degrees']);

        $latitude['seconds'] += 60 * ($latitude['minutes'] - floor($latitude['minutes']));
        $latitude['minutes'] = floor($latitude['minutes']);

        // Долгота
        $longitude['degrees'] = getCoord( $exif['GPSLongitude'][0] );
        $longitude['minutes'] = getCoord( $exif['GPSLongitude'][1] );
        $longitude['seconds'] = getCoord( $exif['GPSLongitude'][2] );

        $longitude['minutes'] += 60 * ($longitude['degrees'] - floor($longitude['degrees']));
        $longitude['degrees'] = floor($longitude['degrees']);

        $longitude['seconds'] += 60 * ($longitude['minutes'] - floor($longitude['minutes']));
        $longitude['minutes'] = floor($longitude['minutes']);

        $choord['latt'] = degreesToDecimal($latitude['degrees'], $latitude['minutes'], $latitude['seconds']);
        $choord['long'] = degreesToDecimal($longitude['degrees'], $longitude['minutes'], $longitude['seconds']);
        /*echo "<a href=\"https://maps.google.com/maps?q="
                . ($exif['GPSLatitudeRef'] == 'S' ? '-' : '') 
                . $latitude['degrees'] 
                . "+" . $latitude['minutes']
                . "'+" . $latitude['seconds'] . "'',+"
                . ($exif['GPSLongitudeRef'] == 'W' ? '-' : '') 
                . $longitude['degrees']
                . "+" . $longitude['minutes'] . "'+"
                . $longitude['seconds'] . "''\" target=\"_blank\">Показать на карте</a>";*/
    }
}
$firstPoint = 'Тулун, Иркутская область';
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>TODO supply a title</title>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

<!-- Optional theme -->

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
        <script src="http://yandex.st/jquery/2.1.0/jquery.min.js"></script>
        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
        
        <script>
            function mTokm($metr) {
                return Math.round($metr / 1000);
            }
            $(function(){
                var myMap;
                ymaps.ready(init);
                $('#submit').click(function(){
                    route([
                        $('#first-point').val(),
                        [<?=$choord['latt'];?>, <?=$choord['long'];?>],
                    ]);
                });
                function init() {
                    myMap = new ymaps.Map ("map", {
                        center: [52.286551, 104.270704], 
                        type: 'yandex#map',
                        zoom: 8,
                        behaviors: ['default', 'scrollZoom']
                    });
                }
                
                function route(points) {
                    ymaps.route(points, {
                        mapStateAutoApply: true
                    }).then(function (route) {
                        /*route.getPaths().options.set({
                            // в балуне выводим только информацию о времени движения с учетом пробок
                            balloonContenBodyLayout: ymaps.templateLayoutFactory.createClass('$[properties.humanJamsTime]'),
                            // можно выставить настройки графики маршруту
                            strokeColor: '0000ffff',
                            opacity: 0.9
                        });*/
                        var points = route.getWayPoints(),
                            lastPoint = points.getLength() - 1;
                    
                        // Задаем стиль метки - иконки будут красного цвета, и
                        // их изображения будут растягиваться под контент.
                        points.options.set('preset', 'twirl#redStretchyIcon');
                        points.get(0).properties.set('iconContent', $('#first-point').val());
                        points.get(lastPoint).properties.set('iconContent', 'Фотография ' + mTokm(route.getLength()) + ' км');
                        
                        console.log(mTokm(route.getLength()));
                        // добавляем маршрут на карту
                        myMap.geoObjects.add(route);
                    }, function (error) {
                        alert('Возникла ошибка: ' + error.message);
                    });
                }
            });
            
        </script>
    </head>
    <body>
        
        <div class="container">
            <form class="form-inline" role="form">
                <div class="form-group">
                    <label class="sr-only" for="exampleInputEmail2">начальная точка</label>
                    <input type="text" class="form-control" id="first-point" value="<?=$firstPoint;?>" placeholder="Начальная точка">
                </div>
                <button id="submit" type="button" class="btn btn-success">Рассчитать</button>
            </form>
            <div><?php echo ($exif['GPSLatitudeRef'] == 'S' ? '-' : '') 
                    . $latitude['degrees'] . '&deg;'
                    . $latitude['minutes']
                    . "'" . $latitude['seconds'] . "\"N, "
                    . ($exif['GPSLongitudeRef'] == 'W' ? '-' : '') 
                    . $longitude['degrees'] . '&deg;'
                    . $longitude['minutes'] . "'"
                    . $longitude['seconds'] . "\"E";?></div>
            
            <div id="map" style="width: 800px; height: 600px"></div>
        
        </div>
    </body>
</html>