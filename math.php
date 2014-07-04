<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once 'function.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
        
$mysqli = db::getInstance();
$error = FALSE;
echo '<pre>';

$filelist = getFiles();


// Список обработанных изображений
$images = array();
$i = 0;
$points = array();
foreach ($filelist as $item) {
    $imgPath = $item;
    if (exif_imagetype($imgPath)) {
        $exif = exif_read_data($imgPath);
        //print_r($exif);
        
        // Широта
        $latitude['degrees'] = getCoord( $exif['GPSLatitude'][0] );
        $latitude['minutes'] = getCoord( $exif['GPSLatitude'][1] );
        $latitude['seconds'] = getCoord( $exif['GPSLatitude'][2] );

        if ($latitude['degrees'] == 0 && $latitude['minutes'] == 0 && $latitude['seconds'] == 0) {
            echo 'Координаты не найдены' . "\n";
        } else {
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

            $points[$i]['gpslatitude'] = degreesToDecimal($latitude['degrees'], $latitude['minutes'], $latitude['seconds']);
            $points[$i]['gpslongitude'] = degreesToDecimal($longitude['degrees'], $longitude['minutes'], $longitude['seconds']);
            
            $m1 = ($exif['GPSLatitudeRef'] == 'S' ? '-' : '') 
                    . $latitude['degrees'] . '&deg;'
                    . $latitude['minutes']
                    . "'" . $latitude['seconds'] . "\"N";
            $m2 = ($exif['GPSLongitudeRef'] == 'W' ? '-' : '') 
                    . $longitude['degrees'] . '&deg;'
                    . $longitude['minutes'] . "'"
                    . $longitude['seconds'] . "\"E";
            $images[] = array(
                'src'          => $imgPath,
                'gpslatitude'  => $points[$i]['gpslatitude'],
                'gpslongitude' => $points[$i]['gpslongitude'],
                'geolat'       => $m1,
                'geolong'      => $m2
            );
            $i++;
        }
    }
}

$distance = $mysqli->getKilometrs();
$roads = $mysqli->roads();
if (!empty($distance) && !empty($images)) {
    foreach ($images as &$point) {
        $pillars = array();
        $newdistance = array();
        foreach ($distance as $row){
            $pillars[] = $row;

            // Вычисляем расстояние между двумя точками
            $newdistance[$row['kmid']] = getDistance(
                    $row['gpslatitude'], 
                    $row['gpslongitude'], 
                    $point['gpslatitude'], 
                    $point['gpslongitude']
                );
        }
        $notsort = $newdistance;
        
        asort($newdistance);
        
        reset($newdistance);
        $kmid = key($newdistance);
        $minValue = $newdistance[$kmid];
        $km = "";
        foreach ($distance as $row) {
            if ($kmid == $row['kmid']) {
                $roadId = $row['roadid'];
                $km = $row['title'];
            }
        }
        
        $roadName = "";
        foreach ($roads as $road) {
            if ($roadId == $road['roadid']) {
                $roadName = $road['roadname'];
            }
        }
        
        echo floor($minValue) . ' метров <br>';
        $point['dist'] = floor($minValue);
        $point['km'] = $km;
        $point['roadid'] = $roadId;
        $point['road'] = $roadName;
    }
    unset($distance);
}

print_r($images);
$renderImages = TRUE;
if ($renderImages) {
    include 'WideImage/WideImage.php';

    foreach ($images as $imageVal) {
        $image = WideImage::load($imageVal['src']); // 100x150
        $resizedImg = $image->resize('1600');
        unset($image);
        $white = $resizedImg->allocateColor(155, 155, 155);
        $newImage = $resizedImg->resizeCanvas('100%', '100%+100', 0, 0, $white);

        $choord1 = $imageVal['geolat'];
        $choord2 = $imageVal['geolong'];
        $text = $imageVal['road'] 
             . "\n{$imageVal['km']} ($choord1, $choord2) = " . $imageVal['dist'] . ' км.';

        //$text2 = "ООО \"Рога и Копыта\"";

        $textColor = $newImage->allocateColor(0, 0, 0);

        $canvas = $newImage->getCanvas();
        $canvas->useFont('TIMCYR_TTF/TIMCYR.TTF', 24, $textColor);
        $canvas->writeText('left + 10', 'bottom - 20', $text);
        //$canvas->writeText('right - 220', 'bottom - 40', $text2);

        $mask = WideImage::load('PastedGraphic-1.png');

        $maskImage = $newImage->merge($mask, 'right - 20', 'bottom', 100);
        $info = pathinfo($imageVal['src']);
        $maskImage->saveToFile('img/' . str_replace(" ", "_", $info['filename']) . '_render.' . $info['extension']);
    }
    
}


$mysqli->close();
