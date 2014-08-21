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
        //include  $appConfig['path_to_swift'] . 'swift_required.php';
        $emailDataFile = 'emaildata.txt';
        if (isset($_REQUEST['files'])) {
            $files = $_REQUEST['files'];
        } else {
            $files = getFiles($appConfig['upload_dir']);
        }
        
        if (isset($appConfig['upload_spec_dir']) && isset($appConfig['render_spec_dir'])) {
            $potok2 = getFiles($appConfig['upload_spec_dir']);
            $outputDir = $appConfig['image_dir'] . '/' . trim($appConfig['render_spec_dir'], '/') . '/';
            if ($potok2) {
                foreach ($potok2 as $filePath) {
                    if (file_exists($filePath)) {
                        $pathInfo =pathinfo($filePath); 
                        $dir = $pathInfo['dirname'];
                        // Обрезаем путь
                        $pos = mb_strrpos($dir, $appConfig['upload_spec_dir']) + mb_strlen($appConfig['upload_spec_dir']) + 1;

                        $paths = explode('/', mb_substr($dir, $pos));
                        $pathItem = mb_substr($dir, $pos);
                        if (!file_exists($outputDir . $pathItem)) {
                            mkdir($outputDir . $pathItem);
                        }
                        $files[] = $filePath;
                    }
                }
                
            }
        }
        
        $roadsModel = new Roads($appConfig);
        // Километровые столбики
        $distance = $roadsModel->getKilometrs();
        // Дороги
        $roads = $roadsModel->roads();
        $limit = $appConfig['max_rendered_files'];
        $i = 0;
        $msg = "";
        if (!file_exists('old/')) {
            mkdir("old", 0777);
        }
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
        
        $efile = New File($emailDataFile);
        $efile->open("r+");
        $lines = $efile->countLines();
        $efile->closeFp();
        if ( $lines > 100 ) {
            sendEmailLogData();
        }
        
        if (isset($msg) && !empty($msg)) {
            $efile = New File($emailDataFile);
            $efile->open();
            $efile->writeToFile($msg);
            $efile->closeFp();
        }

        /* Определение расстояния */
        if (!empty($distance) && !empty($images)) {
            foreach ($images as &$point) {
                prepareDistance($distance, $roads, $point);
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
                
                /* Поиск ФИО */
                $fioText = "";
                if ($photographer) {
                    foreach ($photographer as $phgKey => $phgItem) {
                        if (strripos($info['filename'], $phgKey)) {
                            $fioText = $phgItem;
                        }
                    }
                }
                
                /* Определяем регион куда записывать файл. к какой папке относится изобрж. */
                $regionName = 'undefined';
                if (!file_exists($appConfig['image_dir'] . '/' .$regionName)) {
                    mkdir($appConfig['image_dir'] . '/' . $regionName, 0777);
                }
                
                
                
                $regions = $roadsModel->getDirectory($imageVal['km']);
                if ($regions) {
                    
                    $regionName = $regions[0]['dirname'];
                    $regionNameRu = (isset($regions[0]['nameRu']) ? $regions[0]['nameRu'] : $regions[0]['dirname']);
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
                    

                    if (!file_exists($appConfig['image_dir'] . '/' .$regionName)) {
                        mkdir($appConfig['image_dir'] . '/' . $regionName, 0777);
                    }
                }
                
                $maskImage = imgProcessing($imageVal, $appConfig, $fioText, $regionName);

                $renderFileName = getRenderFileName($info['filename']);

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
                //unlink($imageVal['src']);
            }
        }
        break;
    case 'handle_priority':
        //include 'WideImage/WideImage.php';
        
        $files = getFiles($appConfig['upload_spec_dir']);
        $outputDir = $appConfig['image_dir'] . '/' . trim($appConfig['render_spec_dir'], '/') . '/';
        if ($files) {
            foreach ($files as $filePath) {
                if (file_exists($filePath)) {
                    $pathInfo =pathinfo($filePath); 
                    $dir = $pathInfo['dirname'];
                    // Обрезаем путь
                    $pos = mb_strrpos($dir, $appConfig['upload_spec_dir']) + mb_strlen($appConfig['upload_spec_dir']) + 1;
                    
                    $paths = explode('/', mb_substr($dir, $pos));
                    $pathItem = mb_substr($dir, $pos);
                    if (!file_exists($outputDir . $pathItem)) {
                        mkdir($outputDir . $pathItem);
                    }
                }
            }
        }
        
        
        //mkdir($appConfig['image_dir'] . '/' . $appConfig['render_spec_dir'] . '/' . $dirs[2]);
        break;
    case 'test':
        var_dump(file_exists('old/'));
        //sendEmailLogData();
        /*include  $appConfig['path_to_swift'] . 'swift_required.php';
        $emailDataFile = 'emaildata.txt';
        $efile = New File($emailDataFile);
        $efile->open("r+");
        var_dump($efile->countLines());
        $efile->closeFp();*/
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