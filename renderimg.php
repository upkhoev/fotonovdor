<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include 'WideImage/WideImage.php';

$image = WideImage::load('img/2014-02-12 13.10.26.jpg'); // 100x150
$white = $image->allocateColor(155, 155, 155);
$newImage = $image->resizeCanvas('100%', '100%+100', 0, 0, $white);

$text = "Дорога А-331 «Вилюй» Тулун – Братск – Усть – Кут – Мирный – Якутск \n"
        ."Мост ч/р Ближний км 349+004 (56°24'28.51\"N, 103°6'45.47\"E)";

$text2 = "ООО \"Рога и Копыта\"";

$textColor = $newImage->allocateColor(0, 0, 0);

$canvas = $newImage->getCanvas();
$canvas->useFont('TIMCYR_TTF/TIMCYR.TTF', 24, $textColor);
$canvas->writeText('left + 40', 'bottom - 20', $text);
$canvas->writeText('right - 160', 'bottom - 30', $text2);

$mask = WideImage::load('gif.png');

$maskImage = $newImage->merge($mask, 'right - 40', 'bottom', 100);

$maskImage->saveToFile('img/newimage.jpg');
//$maskImage->output('jpg', 45);