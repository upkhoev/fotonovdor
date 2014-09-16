<?php
define('APPPATH', rtrim(dirname(__FILE__), '/') . '/');
require_once 'include/init.php';
$start = microtime(true);

$appView = View::getInstance();
$mysqli = db::getInstance($appConfig['db']);
$indexFile = 'index.php';
$menu = array(
    $indexFile . '?do=files'     => 'список обработанных изображений',
    $indexFile . '?do=newfiles'  => 'список загруженных изображений',
    $indexFile . '?do=handle_imgs' => 'запуск обработчика',
    $indexFile . '?do=setting'   => 'Настройки'
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
        
        $perPage = 5;
        
        $page = (isset($_GET['page']) ? (int) $_GET['page'] : 1);
        for ( $j = ($perPage * ($page - 1)); $j < $perPage * $page; $j++) {
            if (isset($files[$j])) {
                $val = $files[$j];
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
        
        $numPages = 4;
        echo '</div>';
        echo '<div class="row"><ul class="pagination">';
        echo '<li class="'.($page <= 1 ? 'disabled' : '').'"><a href="?do=newfiles&amp;page='.($page - 1).'">&laquo;</a></li>';
        for ($i=0; $i < ceil(count($files) / $perPage); $i++) {
            $class = '';
            if ($page == $i + 1) {
                $class .= ' active';
            }
            echo '<li class="' . $class . '"><a href="?do=newfiles&amp;page='.($i+1).'">' . ($i+1) . ' </a></li>';
        }
        echo '<li class="'.(count($files)-($perPage*$page) <= 0 ? 'disabled' : '').'"><a href="?do=newfiles&amp;page='.($page + 1).'">&raquo;</a></li>';
        echo '</ul></div>';
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
        $emailDataFile = APPPATH . 'emaildata.txt';
        $files = array();
        if (isset($_REQUEST['files'])) {
            $files = $_REQUEST['files'];
        } else {
            // Получаем поток №2
            if (isset($appConfig['upload_spec_dir']) && isset($appConfig['priority_spec_paths']) && !empty($appConfig['priority_spec_paths'])) {
                foreach ($appConfig['priority_spec_paths'] as $path) {
                    $path = trim($appConfig['upload_spec_dir'], '/') . '/' . $path;
                    
                    if ( isset($appConfig['convertToCp1251']) && $appConfig['convertToCp1251'] ) {
                        $path = convertToCp1251($path);
                    }
                    $potok2 = getFiles($path);
                    $outputDir = $appConfig['image_dir'] . '/';
                    if (!is_writable ($outputDir)) {
                        echo '<p style="font-size:24px;color:red">Папка не доступна для записи : ' . $outputDir . '</p>';
                        log_message ('The folder is not writable:' . $newDir);
                    }
                    if ($potok2 && !empty($potok2)) {
                        foreach ($potok2 as $filePath) {
                            if (file_exists($filePath)) {
                                
                                $pathInfo = pathinfo($filePath); 
                                $dir = $pathInfo['dirname'];
                                // Обрезаем путь
                                $pos = mb_strrpos($dir, $appConfig['upload_spec_dir']) + mb_strlen($appConfig['upload_spec_dir']) + 1;

                                $pathItem = mb_substr($dir, $pos);
                                
                                $newDir = $appConfig['render_spec_dir'] . '/' . ltrim($pathItem, '/');
                                // Создаем папки
                                if (!file_exists($newDir)) {
                                    if (mkdir($newDir)) {
                                        log_message ('Failed to create a folder ' . $newDir);
                                    }
                                }
                                $files[] = $filePath;
                            }
                        }
                    }
                }
            }
            $potok1 = getFiles($appConfig['upload_dir']);
            if ($potok1) {
                $files = array_merge($files, $potok1);
            }
        }
        
        $roadsModel = new Roads($appConfig);
        // Километровые столбики
        $distance = $roadsModel->getKilometrs();
        // Дороги
        $roads = $roadsModel->roads();
        $limit = $appConfig['max_rendered_files'];
        
        $msg = "";
        $badDir = 'old/';
        if ( !file_exists($badDir) ) {
            mkdir("old", 0777);
        }
        
        $i = 0;
        while ($i < $limit && isset($files[$i])) {
            
            $item = $files[$i];
            
            if ($appConfig['convertToUtf8']) {
                $item = convertToUtf8($item);
            }

            // Поиск файла в потоке
            if ( !$roadsModel->searchInThread($item, time() - 600) ) {
                $roadsModel->setThread($item);
                $imgPath = ltrim($item, '/');
                $file = new File($imgPath);

                $fileData = $file->getImgDataAttr($imgPath);
                if ($fileData === FALSE) {
                    $msg .= '[' . date("r") . '] [error] ';
                    $msg .= 'Имя файла: ' . $imgPath;
                    $msg .= ' : ' . $file->displayError(). "\n";

                    /* Перемещаем файл */
                    $path_parts = pathinfo($imgPath);
                    rename( $imgPath, $badDir . $path_parts['basename'] );
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
            $i++;
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
            if ($efile) {
                $efile->open();
                $efile->writeToFile($msg);
                $efile->closeFp();
            }
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
                
                if (mb_strrpos($imageVal['src'], $appConfig['upload_spec_dir']) !== FALSE) {
                    $imgPathInfo = pathinfo($imageVal['src']);
                    $pos = mb_strrpos($imgPathInfo['dirname'], $appConfig['upload_spec_dir']) + mb_strlen($appConfig['upload_spec_dir']) + 1;

                    $pathItem = mb_substr($imgPathInfo['dirname'], $pos);
                    $renderPathFile = $appConfig['render_spec_dir']. '/' . trim($pathItem, '/')
                            . '/' . $renderFileName;
                } else {
                    $renderPathFile = $appConfig['image_dir'] . '/'
                        . (isset($regionName) ? $regionName . '/' : '') . $renderFileName;
                }
                
                $roadsModel->removeInThread($imageVal['src']);
                
                $maskImage->saveToFile($renderPathFile);

                $roadsModel->writeLog($maxLodId, $info['filename'] . '.' . $info['extension'], $imageVal['km']);
                $maxLodId++;
                
                if ($saveToTysyacha) {
                    $maskImage->saveToFile($appConfig['copyDir'] . '/'. $renderFileName);
                }
                unset($maskImage, $newImage);
                // Удаляем старый файл
                unlink($imageVal['src']);
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
        
        break;
    case 'test':
        $filePath ='Ph_Upload_Special/ДТП/15 09 ДТП 460/F07_3730_16-09-2014_541735ca866b6.jpg';
        $appConfig['upload_spec_dir'] = 'Ph_Upload_Special';
        $pathInfo = pathinfo($filePath);
        $dir = $pathInfo['dirname'];
        $pos = mb_strrpos($dir, $appConfig['upload_spec_dir']) + mb_strlen($appConfig['upload_spec_dir']) + 1;
        $pathItem = mb_substr($dir, $pos);

        var_dump($pathItem);
        break;
    case 'update_database':
        if (!$mysqli) {
            exit('database connection fail!<br>');
        }
        $query = "CREATE TABLE IF NOT EXISTS `threads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filepath` varchar(300) NOT NULL,
  `datetime` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $mysqli->query($query);
        $result = $mysqli->query("SHOW TABLES LIKE 'threads'");
        if ($result->num_rows == 1) {
            echo 'Обновление выполнено успешно<br>';
        } else {
            echo 'Table `threads` does not exist<br>';
        }
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