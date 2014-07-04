-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Май 13 2014 г., 08:04
-- Версия сервера: 5.5.37
-- Версия PHP: 5.3.10-1ubuntu3.11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `roads`
--

-- --------------------------------------------------------

--
-- Структура таблицы `setting`
--

CREATE TABLE IF NOT EXISTS `setting` (
  `settingId` int(5) NOT NULL AUTO_INCREMENT,
  `settingKey` char(100) NOT NULL,
  `settingVal` char(255) NOT NULL,
  `description` varchar(300) NOT NULL,
  PRIMARY KEY (`settingId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Дамп данных таблицы `setting`
--

INSERT INTO `setting` (`settingId`, `settingKey`, `settingVal`, `description`) VALUES
(1, 'upload_dir', 'uploads', 'Папка для загрузки фотографий'),
(2, 'logo_path', 'PastedGraphic-1.png', 'Путь до логотипа'),
(3, 'image_dir', 'img', 'Папка для готовых изображений'),
(4, 'font', 'TIMCYR_TTF/TIMCYR.TTF', 'Шрифт'),
(5, 'font_size', '24', 'Размер шрифта'),
(6, 'report_email', 'uphoev@gmail.com', 'Почта'),
(7, 'footer_bg_color', '#e49b0f', 'Цвет фона'),
(8, 'down_scaling_height', '1000', 'Высота изображений');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
