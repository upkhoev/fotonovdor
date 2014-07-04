<?php
$start = microtime(true);
require_once 'function.php';

$appView = View::getInstance();
$mysqli = db::getInstance($appConfig['db']);

$menu = array(
    'index.php?do=files'     => 'список обработанных изображений',
    'index.php?do=newfiles'  => 'список загруженных изображений',
    'index.php?do=handle_imgs' => 'запуск обработчика',
    'index.php?do=setting'   => 'Настройки'
);

$appView->load('header', array('menu' => $menu));

$do = (isset($_REQUEST['do']) ? $_REQUEST['do'] : FALSE);

switch ($do) {
    case 'send_test_email':
        include 'swift_required.php';
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject('Hi friend')

        // Set the From address with an associative array
        ->setFrom(array('upkhoev.igor@yandex.ru' => 'Uphoev Igor'))
        ->setTo(array('uphoev@gmail.com'))
        // Give it a body
        ->setBody('Как твои дела');
        break;
    case 'files':
        $files = getFiles($appConfig['image_dir']);
        echo '<div class="row">';
        foreach ( $files as $key => $val ) {
            echo $appView->load('result_images', array(
                'origin' => $val,
                'val'    => $val
            ));
            $img = '<img src="' . $val . '" width="200" height="200" />';
            //echo '<li style="float: left;padding:20px;"><a href="' . $val . '">' . $img . '</a></li>';
        }
        echo '</div>';
        break;
    case 'remove_file':
        if (isset($_REQUEST['file'])) {
            $fileName = $_REQUEST['file'];

            if (file_exists(ltrim($fileName, '/'))) {
                unlink(ltrim($fileName, '/'));
            }

            echo '<a href="#" onclick="return history.back();">Назад</a>';
        }
        break;

    case 'newfiles':
        include 'WideImage/WideImage.php';
        $width = 230;
        $files = getFiles($appConfig['upload_dir']);
        echo '<div class="row">';
		$i = 0;
        foreach ( $files as $key => $val ) {
			if ($i < 20) {
            $imgPath = $val;
            $res = getAttrDataImg($imgPath);

            $aImgPath = $val;

            $pathInfo = pathinfo($aImgPath);
            $name = str_replace(" ", "_", $pathInfo['filename']);
            $thumbFile = $appConfig['cache_dir'] . '/' . $name . '_thumb.' . $pathInfo['extension'];

            if (!file_exists($thumbFile)) {
                $image = WideImage::load($aImgPath);
                $resizedImg = $image->resize($width);
                $resizedImg->saveToFile($thumbFile);
                unset($image);
            }

            $appView->load('prepare_img', array(
                'origin' => $imgPath,
                'val'    => $thumbFile,
                'error'  => $res
            ));
			$i++;
			}
        }
        echo '</div>';
        break;

    case "files_list":

        $files = getFiles($appConfig['upload_dir']);
        print_r($files);

        $dirPath = array(
            '1' => 'Bratsk_Tulun',
            '2' => 'Ust-kut_Verhnemarkovo',
            '3' => 'Obhod_Ust-kut'
        );
        //mkdir('img/Bratsk_Tulun', 0777);
        $roadsModel = new Roads($appConfig);
        $distance = $roadsModel->getKilometrs();
        foreach ($distance as $val) {
            $kmPos = mb_strpos($val['title'], 'км');
            preg_match('/\D+/', mb_substr($val['title'], $kmPos + mb_strlen('км')), $matches, PREG_OFFSET_CAPTURE);
            $lastPos = FALSE;
            if (!empty($matches)) {
                $lastPos = $matches[0][1];
            }
            //$kmPosLast = mb_strpos($thumbFile, $aImgPath);
            if (!$kmPos) {
                var_dump($val);
            }
            $new[] = array(
                'kmid' => $val['kmid'],
                'title' => $val['title'],
                'km' => intval (mb_substr($val['title'], $kmPos + mb_strlen('км'), ($lastPos ? $lastPos : (mb_strlen($val['title']) - $kmPos + mb_strlen('км')))))
            );

            //$roadsModel->updateKilometrs($val['kmid'], intval (mb_substr($val['title'], $kmPos + mb_strlen('км'), ($lastPos ? $lastPos : (mb_strlen($val['title']) - $kmPos + mb_strlen('км'))))));
        }
        print_r($new);
        break;

    case 'handle_imgs':
        include 'WideImage/WideImage.php';
        include  $appConfig['path_to_swift'] . 'swift_required.php';
        $emailDataFile = 'emaildata.txt';
        if (isset($_REQUEST['files'])) {
            $files = $_REQUEST['files'];
        } else {
            $files = getFiles($appConfig['upload_dir']);
        }
        $roadsModel = new Roads($appConfig);
        // Километровые столбики
        $distance = $roadsModel->getKilometrs();
        // Дороги
        $roads = $roadsModel->roads();
        $limit = $appConfig['max_rendered_files'];
        $i = 0;
        $msg = "";
        for ($i = 0; $i <= $limit; $i++) {
            if (isset($files[$i])) {
                $item = $files[$i];
                $imgPath = ltrim($item, '/');
                $file = new File($imgPath);

                $fileData = $file->getImgDataAttr($imgPath);
                if ($fileData === FALSE) {
                    $msg .= '[' . date("r") . '] [error] ';
                    $msg .= 'Имя файла: ' . $imgPath;
                    $msg .= ' : ' . $file->displayError(). "\n";


                    /* Перемещаем файл */
                    $path_parts = pathinfo($imgPath);
                    rename( $imgPath, 'old/' . $path_parts['basename'] );
                    /*copy( $imgPath, 'old/' . $path_parts['basename'] );
                    unlink($imgPath);*/
                    echo '<p class="label label-danger">[' . date("r") . '] '
                        . $imgPath . ' ('
                        . $file->displayError() . ')</p><br>';
                } else {
                    $images[] = $fileData;
                    //echo '<p class="label label-success">' . $imgPath . '</p><br>';
                }
            }
        }

        if (isset($msg) && !empty($msg)) {
            $efile = New File($emailDataFile);
            $efile->open();
            $efile->writeToFile($msg);
            $efile->closeFp();
        }


        $efile = New File($emailDataFile);
        $efile->open("r+");
        // Отправляем содержимое файла на почту через определенный промежуток
        $email_send_file = New File('email_send_time.txt');
        $email_send_file->open("r");
        $send_file_time = $email_send_file->readData();
        $fileModDate = intval($send_file_time);
        $email_send_file->closeFp();
        $interval = $appConfig['email_send_interval'];

        if (
            time() - $fileModDate > $interval
                &&
            $appConfig['send_email'] == 1
                &&
            class_exists('Swift_Message')
        ) {
            $msg = $efile->readData();
            if (!empty($msg)) {
                $transport = Swift_SmtpTransport::newInstance($appConfig['smtp_transport']['smtp_server'], $appConfig['smtp_transport']['port'], 'ssl')
                  ->setUsername($appConfig['smtp_transport']['username'])
                  ->setPassword($appConfig['smtp_transport']['password']);
                // Create the Mailer using your created Transport
                $mailer = Swift_Mailer::newInstance($transport);
                // Create the message
                $message = Swift_Message::newInstance()
                    // Give the message a subject
                    ->setSubject('Лог обработки фотографий ' . date("r"))

                    // Set the From address with an associative array
                    ->setFrom(array('it@novdor.ru' => 'Admin'))
                    ->setTo(array($appConfig['report_email']))
                    // Give it a body
                    ->setBody($msg);
                // Send the message
                $result = $mailer->send($message);
                $email_send_file = New File('email_send_time.txt');
                $email_send_file->open("w+");
                $email_send_file->writeToFile(time());
                $email_send_file->closeFp();
            }
            $newFile = New File($emailDataFile);
            $newFile->open("w+");
            $newFile->closeFp();
        }
        $efile->closeFp();

        /* Определение расстояния */
        if (!empty($distance) && !empty($images)) {
            foreach ($images as &$point) {
                $newdistance = array();
                foreach ($distance as $row){

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
                next($newdistance);
                $seckmId = key($newdistance);
                // Вычисляем какой столб от начала(дальше или ближе)
                if ($kmid - $seckmId > 0) {
                    // столб дальний
                    $point['plus'] = TRUE;
                } else {
                    // столб ближний
                    $point['plus'] = FALSE;
                }

                $km = "";
                foreach ($distance as $row) {
                    if ($kmid == $row['kmid']) {
                        $roadId = $row['roadid'];
                        $kmValue = $row['km'];
                        $km = $row['title'];
                    }
                }
                if (!$point['plus']) {
                    $kmValue = $kmValue - 1;
                }

                $roadName = "";
                foreach ($roads as $road) {
                    if ($roadId == $road['roadid']) {
                        $roadName = $road['title'];
                    }
                }
                
                if (floor($minValue) < 1000) {
                    if (!$point['plus']) {
                        $point['dist'] = 1000 - floor($minValue);
                    } else {
                        $point['dist'] = floor($minValue);
                    }
                } else {
                    $point['dist'] = '';
                }
                
                

                $point['kmtitle'] = $km;
                $point['km'] = $kmValue;
                $point['roadid'] = $roadId;
                $point['road'] = $roadName;
            }
            unset($distance);
        }



        if (isset($images) && !empty($images)) {
            $saveToTysyacha = TRUE;
            if ($saveToTysyacha) {

                clearCopyDir($appConfig['copyDir'], $appConfig['maxCopyFiles'] - count($images));
                // Копируем файл
            }
            $photographer = config::getPhotographer();
            $maxLodId = $roadsModel->getMaxLogId();
            foreach ($images as &$imageVal) {
                $info = pathinfo($imageVal['src']);
                $image = WideImage::load($imageVal['src']);
                list($width, $height, $type, $attr) = getimagesize($imageVal['src']);
                $imgRatio = round($width / $height, 2);
                $widescreen = 1.77;
                if ($appConfig['crop_img_to_widescreen'] && $imgRatio < $widescreen) {
                    $cropedImg = $image->crop('center', 'bottom', $width, round($width / $widescreen) - $appConfig['footer_bg_color']);
                    unset($image);
                } else {
                    $cropedImg = $image;
                }

                $resizedImg = $cropedImg->resize(NULL, $appConfig['down_scaling_height']);

                $rgb = hex2rgb($appConfig['footer_bg_color']);
                $white = $resizedImg->allocateColor(
                        $rgb[0],
                        $rgb[1],
                        $rgb[2]
                    );

                // Добавляем внизу плашку
                $cropImg = $resizedImg->resizeCanvas('100%', '100%+' . $appConfig['footer_height'], 0, 0, $white);

                // обрезаем
                $newImage = $cropImg->crop(0, $appConfig['footer_height'], '100%', '100%');

                $choord1 = $imageVal['geolat'];
                $choord2 = $imageVal['geolong'];
                $text = $imageVal['road'] . ' '
                     . mb_substr($imageVal['kmtitle'], mb_strpos($imageVal['kmtitle'], 'км')) .' '. ($imageVal['plus'] ? '+' : '-') . " {$imageVal['dist']} м.";
                if (isset($appConfig['color_text'])) {
                    $hexTxtColor = hex2rgb($appConfig['color_text']);
                    $textColor = $newImage->allocateColor($hexTxtColor[0], $hexTxtColor[1], $hexTxtColor[2]);
                } else {
                    $textColor = $newImage->allocateColor(0, 0, 0);
                }

                $bottom = 15;
                /* 1 */
                $text = 'км';
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], 24, $textColor);
                $canvas->writeText('left + 10', 'bottom - ' . $bottom, $text);
                //$canvas->writeText('right - 220', 'bottom - 40', $text2);

                /* Столб */
                //$text = mb_substr($imageVal['km'], mb_strpos($imageVal['km'], 'км'));
                $text = $imageVal['km'];
                $count = 1;
                //$text = str_replace('км', '', $text, $count);
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], $appConfig['font_size'], $textColor);
                $canvas->writeText('left + 60', 'bottom - ' . $bottom, $text);

                /* Расстояние до столба */
                $text = '+' . $imageVal['dist'];
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], 24, $textColor);
                $canvas->writeText('left + 185', 'bottom - ' . $bottom, $text);

                /* Название дороги */
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], 12, $textColor);
                $canvas->writeText('left + 280', 'bottom - ' . $bottom, $imageVal['road']);

                /* Дата */
                $time = strtotime($imageVal['date']);
                $month = getRuMonth(date('m', $time));
                $text = date('d', $time) .'-'. mb_strtoupper(mb_substr($month, 0, 1)) . mb_substr($month, 1) .'-'. date('Y', $time);
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], 32, $textColor);
                $canvas->writeText('left + 565', 'bottom - ' . $bottom, $text);

                /* Время */
                $text = date('H:i:s');
                $canvas = $newImage->getCanvas();
                $canvas->useFont($appConfig['font'], 12, $textColor);
                $canvas->writeText('left + 950', 'bottom - ' . $bottom, $text);

                // Координаты
                $choordCanvas = $newImage->getCanvas();
                $choordCanvas->useFont($appConfig['font'], 12, $textColor);
                $choordCanvas->writeText( 'left + 1025', 'bottom - ' . $bottom,  "($choord1, $choord2)");

                /* Поиск ФИО */
                $fioText = "";
                if ($photographer) {
                    foreach ($photographer as $phgKey => $phgItem) {
                        if (strripos($info['filename'], $phgKey)) {
                            $fioText = $phgItem;
                        }
                    }
                }
                $authorCanvas = $newImage->getCanvas();
                $authorCanvas->useFont($appConfig['font'], 12, $textColor);
                $authorCanvas->writeText( 'left + 950', 'bottom - 40', $fioText);

                $mask = WideImage::load($appConfig['logo_path']);
                $bigMask = $mask->resize('35%', '35%');

                $logoOffSet = 5;
                $maskImage = $newImage->merge($bigMask, 'right - ' . $logoOffSet, 'bottom', 100);

                $regionName = 'undefined';
                if (!file_exists($appConfig['image_dir'] . '/' .$regionName)) {
                    mkdir($appConfig['image_dir'] . '/' . $regionName, 0777);
                }
                /* Определяем к какой папке относится изобрж. */
                $regions = $roadsModel->getDirectory($imageVal['km']);
                if ($regions) {
                    $regionName = $regions[0]['dirname'];
                    $regionNameRu = $regions[0]['nameRu'];
                    if (count($regions > 0)) {
                        $regInterval = $regions[0]['end'] - $regions[0]['start'];

                        foreach ($regions as $val) {
                            if ($regInterval > $val['end'] - $val['start']) {
                                $regInterval = $val['end'] - $val['start'];
                                $regionName = $val['dirname'];
                                $regionNameRu = $val['nameRu'];
                            }
                        }
                    }
                    
                    $regionCanvas = $maskImage->getCanvas();
                    $regionCanvas->useFont($appConfig['font'], 12, $textColor);
                    $regionCanvas->writeText( 'left + 280', 'bottom - 50', $regionNameRu);

                    if (!file_exists($appConfig['image_dir'] . '/' .$regionName)) {
                        mkdir($appConfig['image_dir'] . '/' . $regionName, 0777);
                    }
                }


                //$renderFileName = $maxLodId . '_render.jpg';
                $renderFileName = str_replace(" ", "_", $info['filename']) . '_' . date('d-m-Y') . '_' . uniqid() . '.jpg';

                //str_replace(" ", "_", $info['filename'])
                $renderPathFile = $appConfig['image_dir'] . '/'
                    . (isset($regionName) ? $regionName . '/' : '') . $renderFileName;
                $maskImage->saveToFile($renderPathFile);

                $roadsModel->writeLog($maxLodId, $info['filename'] . '.' . $info['extension'], $imageVal['km']);

                $maxLodId++;
                if ($saveToTysyacha) {
                    $maskImage->saveToFile($appConfig['copyDir'] . '/'. $renderFileName);
                }
                unset($maskImage, $newImage);
                // Удаляем старый файл
                unlink($imageVal['src']);
                /*echo '<a href="' . $renderPathFile . '" target="_blank" title="Открыть в новом окне" style="max-width: 100%;">'
                        . '<img src="' . $renderPathFile . '" width="100%" alt=""></a>';*/
            }
        }
        break;

    case 'test':
        
        break;
    case 'setting':
        $roadsModel = new Roads($appConfig);
        $setting = $roadsModel->getSetting();
        $appView->load('settingForm', array('setting' => $setting));
        break;

    case 'saveSetting':

        $post = $_POST;
        if ( isset($post['save']) && $post['save']==1 ) {
            $roadsModel = new Roads($appConfig);
            foreach ($post['config'] as $key => $val) {
                $roadsModel->setSetting($key, $val);
            }

            $setting = $roadsModel->getSetting();
            $appView->load('settingForm', array(
                'setting' => $setting,
                'msg'     => 'Настройки сохранены'
            ));
        }
        break;

    default:
        include  $appConfig['path_to_swift'] . 'swift_required.php';
        if (!class_exists('Swift_Message')) {
            echo '<p>Не найдена билблиотека Swift Mail</p>';
        }
        break;
}

echo 'Время выполнения скрипта: '.(microtime(true) - $start).' сек.';
$appView->load('footer');
$mysqli->close();