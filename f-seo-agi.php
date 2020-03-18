<?php
/*
Plugin Name: F-Seo Auto Google Images
Description: Плагин автоматической подгрузки картинок из Google Images в текстовый редактор WordPress
Author: F-Seo
Version: 2.6
Author URI: http://f-seo.ru/
*/

define('FSEO_AGI_CURRENT_VERSION', '2.6');

include __DIR__ . '/AgiGoogleImage.php';

function debug($data)
{
    print("<pre>" . print_r($data, true) . "</pre>");
}

// Подключаем js скрипт и css файл
function fseo_agi_init()
{
    wp_enqueue_style('agi_style', plugins_url('css/agi_style.css', __FILE__), null, FSEO_AGI_CURRENT_VERSION);
    wp_enqueue_script('agi_script', plugins_url('js/agi_script.js', __FILE__), false, FSEO_AGI_CURRENT_VERSION);
}

add_action('admin_init', 'fseo_agi_init');

// страница администрирования
function fseo_agi_setup_menu()
{
    // верхний уровень
    add_menu_page('F-Seo AGI', 'F-Seo AGI', 'manage_option', 'fseo_agi_settings_page', 'sb_admin_fseo_agi');
    // подуровни
    add_submenu_page('fseo_agi_settings_page', 'Настройки', 'Настройки', 'manage_options', 'f-seo-agi', 'sb_admin_fseo_agi_sett');
    // добавляем настройку для плагина
    add_action('admin_init', 'register_agi_settings');
}

add_action('admin_menu', 'fseo_agi_setup_menu');

/**
 * регистрация настроек
 */
function register_agi_settings()
{
    register_setting('agi_settings-group', 'agi_img_churl');
    register_setting('agi_settings-group', 'agi_replace_main');
}

/**
 * экран настроек плагина
 */
function sb_admin_fseo_agi_sett()
{
    echo '<h1>Настройки F-Seo Auto Google Images</h1>';
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && $_POST['agi_img_churl'] !== get_option('agi_img_churl')) {
        update_option('agi_img_churl', $_POST['agi_img_churl']);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && $_POST['agi_replace_main'] !== get_option('agi_replace_main')) {
        update_option('agi_replace_main', $_POST['agi_replace_main']);
    }
    settings_fields('agi_settings-group');
    ?>
    <form method="post">
        <fieldset>
            <label class="clear d_block" for="agi_img_churl">
                <input name="agi_img_churl" type="checkbox"
                       id="agi_img_churl" <?= get_option('agi_img_churl') ? 'checked="checked"' : ''; ?> />
                Убрать "плохие картинки" из выдачи (замедлит поиск в 3 раза)
            </label>
        </fieldset>
        <fieldset>
            <label class="clear d_block" for="agi_replace_main">
                <input name="agi_replace_main" type="checkbox"
                       id="agi_replace_main" <?= get_option('agi_replace_main') ? 'checked="checked"' : ''; ?> />
                Заменять основной файл сжатым (Максимальный размер файла задается в <a
                        href="/wp-admin/options-media.php">настройках медиафайлов, крупный размер</a>)
            </label>
        </fieldset>
        <input type="submit" class="button-primary" value="Сохранить" />
    </form>
    <?php
}

/**
 * задаем ширину картинок из настроек Wordpress
 */
function get_agi_img_width_option()
{
    $options = array();

    if (get_option('medium_size_w') && get_option('large_size_w')) {
        $options[0] = get_option('medium_size_w');
        $options[1] = get_option('large_size_w');
        $options[2] = get_option('thumbnail_size_w');
    } else {
        $options[0] = 300;
        $options[1] = 600;
        $options[2] = 250;
    }
    echo json_encode($options);
    die();
}

add_action('wp_ajax_get_agi_img_width_option', 'get_agi_img_width_option');

/**
 * добавление глобальной переменной для JS
 */
function agi_admin_head()
{
    $id = (int)get_the_ID();
    if ($id) {
        echo '<script type="text/javascript"> var googleImagesPostId = ' . $id . ';</script>';
    }
}

add_action('admin_head', 'agi_admin_head');


/****************************************
 * Функционал поиска и загрузки картинок
 ***************************************/

/**
 * Метод поиска. Вызывается на фронте ajax запросом, возвращает распарсенную выдачу Google
 */
function agi_google_images_search()
{
    /* Параметры запроса */

//    $url = 'https://www.googleapis.com/customsearch/v1?cx=007742504625441601031%3Aacf65gyd015&searchType=image&key=AIzaSyDCzAGJkmgdxJyw1ZOyplX1UrLsJQeCacQ';
    $url = 'https://www.googleapis.com/customsearch/v1?cx=009283422544278304782%3Aak1pddwaklq&searchType=image&key=AIzaSyDTMOIV7ggGRfxAjI9maxh4555jb82Puig';
//    $url = 'https://pokeapi.co/api/v2/pokemon/ditto/';
    // добавляем параметры в запрос
    foreach ($_POST as $key => $value) {
        if ($value && $key !== 'action') $url .= '&' . $key . '=' . $value;
    }
    // основной запрос
    $response = wp_remote_get($url,
        [
            'headers' => [
                'Referer' => site_url() . '/',
                // юзер агент важен - формируются разные ответы
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
            ],
        ]);


    $body = wp_remote_retrieve_body($response); // вернет '' если ошибка
    echo $body;
    die();
}

add_action('wp_ajax_agi_google_images_search', 'agi_google_images_search');


/**
 * Загрузка каринок на сайт - вызывается на фронте ajax запросом
 */
function agi_google_images_upload()
{
    $url = $_POST['url'];
    $width = $_POST['width'];
    $referer = $_POST['referer'];
    $post_id = $_POST['post_id'];
    $search = $_POST['search'];
    $thumb = $_POST['thumb'];
    $orientation = $_POST['orientation'];
    $propotion = $_POST['proportion'];
    try {
        $image = new AgiGoogleImage($url, $referer);
    } catch (Exception $e) {
        header('HTTP/1.0 404 Not Found');
        die();
    }
    // формируем название файла
    $filename = _getFileNameAgi($search) . date('_dHis') . '.jpg';
    if ($image->mime === 'image/png') {
        $filename = _getFileNameAgi($search) . date('_dHis') . '.png';
    }

    // директория для загрузки медиафайлов
    $upload_dir = wp_upload_dir();
    // сжимаем
    $image->compress();
    // загружаем в Wordpress
    $upload_image = wp_upload_bits($filename, null, $image->files);
    // создаем объект для вложения в пост
    $attachment = [
        'guid' => $upload_image['url'],
        'post_mime_type' => $image->mime,
        'post_title' => '',
        'post_content' => '',
        'post_status' => 'inherit',
    ];
    $file_dir = $upload_dir['path'] . '/' . $filename;
    // цепляем вложение к посту
    $attachment_id = wp_insert_attachment($attachment, false, $post_id);
    if (!function_exists('wp_generate_attachment_data')) {
        require_once ABSPATH . 'wp-admin' . '/includes/image.php';
    }
    // добавляем метадату к вложению
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_dir);
    wp_update_attachment_metadata($attachment_id, $attach_data);
    update_attached_file($attachment_id, $file_dir);
    // формируем короткое имя
    $short_name = '';
    if (strpos($filename, '.jpg')) {
        $short_name = str_replace('.jpg', '', $filename);
    }
    if (strpos($filename, '.png')) {
        $short_name = str_replace('.png', '', $filename);
    }
    if (strpos($filename, '.gif')) {
        $short_name = str_replace('.gif', '', $filename);
    }
    // если миниматюра, то связываем с постом

    if ($thumb) {
        update_post_meta($post_id, '_thumbnail_id', $attachment_id);
        $file_dir = $upload_dir['path'] . '/'
            . getFileNameWithSize(
                $short_name . '-' . get_option('medium_size_w'),
                $upload_dir['path'],
                $orientation,
                get_option('medium_size_w'),
                $propotion
            )[0];
    }
//    if ($width) {
//        $file_dir = $upload_dir['path'] . '/'
//            . getFileNameWithSize(
//                $short_name . '-' . get_option($width . '_size_w'),
//                $upload_dir['path'],
//                $orientation,
//                get_option($width . '_size_w'),
//                $propotion
//            )[0];
//    }

    $pos = strpos($file_dir, '/wp-content');
    $file_dir = substr($file_dir, $pos);
    $file_dir = str_replace('/public_html', '', $file_dir);
    if (strpos($file_dir, 'WP_Error')) {
        echo 'WP_Error';
    } else if ($thumb) {
        $result = [];
        $result[0] = $attachment_id;
        $result[1] = $file_dir;
        echo json_encode($result);
    } else {
        echo $file_dir;
    }
    die();
}

add_action('wp_ajax_agi_google_images_upload', 'agi_google_images_upload');

/**
 * Сортировка файлов по дате
 * @param string $path
 * @return array
 */
function listdir_by_date($path)
{
    $dir = opendir($path);
    $list = array();
    while ($file = readdir($dir)) {
        if ($file !== '.' && $file !== '..') {
            // кроме даты создания файлы добавляем ещё и имя
            // чтобы удостоверится, что мы не заменяем ключ массива
            $ctime = filectime($path . '/' . $file) . ',' . $file;
            $list[$ctime] = $file;
        }
    }
    closedir($dir);
    krsort($list);
    return $list;
}

/**
 * Получение имя файла, подходящего по пропорциям
 * @param $find_file_name
 * @param $path
 * @param $orientation
 * @param $width
 * @param $proportion
 * @return mixed
 */
function getFileNameWithSize($find_file_name, $path, $orientation, $width, $proportion)
{
    $names = listdir_by_date($path);
    $i = 0;
    $ar = [];

    foreach ($names as $name) {
        //проверяем имя картинки
        $s = strpos($name, $find_file_name);

        // если имя картинки совпало с искомым
        if ($s || $s === 0) {
            $cut_height = str_replace($find_file_name . 'x', '', $name);
            if (
                ($orientation === 'horizontal' && (int)$width > (int)$cut_height)
                || ($orientation === 'vertical' && (int)$width < (int)$cut_height)
                || ($orientation === 'square' && (int)$width === (int)$cut_height)
            ) {
                $ar[] = [$name, (int)$width / (int)$cut_height];
            } else {
                continue;
            }
        }
        if ($i > 20) {
            break;
        }
        $i++;
    }
    return findNearestProportion($ar, $proportion);
}

/**
 * ищет подходящую по пропорциям картинку
 * @param $ar
 * @param $proportion
 * @return mixed
 */
function findNearestProportion($ar, $proportion)
{
    $max = $ar[0];
    $count = count($ar);
    for ($i = 1; $i < $count; $i++) {
        if (abs($ar[$i][1] - $proportion) < abs($max[1] - $proportion)) {
            $max = $ar[$i];
        }
    }
    return $max;
}

/**
 * Строит имя файла на основе поискового запроса
 * @param string $search
 * @return string
 */
function _getFileNameAgi($search)
{
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V',
        'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
        'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
        'И' => 'I', 'Й' => 'Y', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R',
        'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
        'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        '.' => "_", ' ' => "_", ',' => '_',
        '?' => "_", '!' => "_", "'" => '',
        ':' => "_", '(' => '', ')' => '',
        '-' => '_', ';' => '_', '[' => '_',
        ']' => '_', '{' => '_', '}' => '_',
        '/' => '_', '«' => '', '»' => ''
    );
    $search = str_replace('"', '', $search);
    $search = strtr(trim($search), $converter);
    $upload_dir = wp_upload_dir();
    $number = 0;
    if ($handle = opendir($upload_dir['path'])) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (strpos($entry . '_', $search) === 0) {
                $num = (int)substr($entry, strlen($search) + 1);
                if ($num > $number) {
                    $number = $num;
                }
            }
        }
        closedir($handle);
    }
    return $search . '_' . ($number + 1);
}

/**
 * замена основного файла на large - сжатый и с ресайзом
 * @param $image_data
 * @return mixed
 */
function replace_uploaded_image($image_data)
{
    // если нет большого изображения - выходим
    if (!isset($image_data['sizes']['large'])) {
        return $image_data;
    }
    // путь к загруженным файлам
    $upload_directory = wp_upload_dir();
    // основной файл
    $uploaded_image_location = $upload_directory['basedir'] . '/' . $image_data['file'];
    // large файл
    $large_image_location = $upload_directory['path'] . '/' . $image_data['sizes']['large']['file'];
    // удаляем основной файл
    unlink($uploaded_image_location);
    // на место удаленного основного копируем large с именем основного файла
    copy($large_image_location, $uploaded_image_location);
    // обновить дату и вернуть
    $image_data['width'] = $image_data['sizes']['large']['width'];
    $image_data['height'] = $image_data['sizes']['large']['height'];
    return $image_data;
}

if (get_option('agi_replace_main')) {
    add_filter('wp_generate_attachment_metadata', 'replace_uploaded_image');
}