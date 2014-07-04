<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$config['upload_dir'] = 'upload/Dropbox/photos_upload';
$config['image_dir'] = 'upload/Dropbox/rendered';
$config['logo_path'] = 'PastedGraphic-1.png';
$config['down_scaling_height'] = 1000;
// Цвет фона RGB
$config['footer_bg_color'] = '#e49b0f';

$config['cache_dir'] = 'cache';

$config['show_date_time'] = TRUE;
$config['date_time_size'] = 14; // px

/**
 * piхels
 */
$config['footer_height'] = 80;

$config['font'] = 'fonts/TIMCYR_TTF/TIMCYR.TTF';
$config['font_size'] = 12; // px
$config['color_text'] = '#000';

$config['copyDir'] = 'upload/Dropbox/Noveishaya_Tysyacha';
$config['maxCopyFiles'] = 1000;

$config['report_email'] = 'uphoev@gmail.com';
$config['crop_img_to_widescreen'] = TRUE;
$config['max_rendered_files'] = 10;
// Если swift не установлен,
// расскоментировать вторую строку. include напрямую
//$config['path_to_swift'] = '';
$config['path_to_swift'] = 'swiftmailer-master/lib/';