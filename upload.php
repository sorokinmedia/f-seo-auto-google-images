<?php
require_once('../../../wp-load.php');

agi_google_images_upload();

/**
 * функция загрузки изображения
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
    } catch(Exception $e) {
        header('HTTP/1.0 404 Not Found');
        die();
    }
    if ($image->mime === 'image/png') {
        $filename = _getFileNameAgi($search).date('_dHis') . '.png';
    } else {
        $filename = _getFileNameAgi($search).date('_dHis') . '.jpg';
    }
    $upload_dir = wp_upload_dir();
    $image->compress();
    $upload_image=wp_upload_bits($filename,null, $image->files);
    $attachment = [
        'guid' => $upload_image['url'],
        'post_mime_type' => $image->mime,
        'post_title' => '',
        'post_content' => '',
        'post_status' => 'inherit',
    ];
    $file_dir = $upload_dir['path'] . '/' . $filename;

    $attachment_id = wp_insert_attachment( $attachment, false, $post_id );
    if(!function_exists( 'wp_generate_attachment_data' )){
        require_once ABSPATH . 'wp-admin' . '/includes/image.php';
    }
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_dir);
    wp_update_attachment_metadata( $attachment_id, $attach_data );
    update_attached_file( $attachment_id, $file_dir);

    $short_name = '';
    if (strpos($filename, '.jpg')){
        $short_name = str_replace('.jpg','' , $filename );
    }
    if (strpos($filename, '.png')){
        $short_name = str_replace('.png','' , $filename );
    }
    if (strpos($filename, '.gif')){
        $short_name = str_replace('.gif','' , $filename );
    }
    if ($thumb) {
        update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
        $file_dir = $upload_dir['path'] . '/'
            . getFileNameWithSize(
                $short_name . '-' . get_option('medium_size_w'),
                $upload_dir['path'],
                $orientation,
                get_option('medium_size_w'),
                $propotion
            )[0];
    }
    if ($width) {
        $file_dir = $upload_dir['path'] . '/'
            . getFileNameWithSize(
                $short_name . '-' . get_option($width . '_size_w'),
                $upload_dir['path'],$orientation,get_option($width . '_size_w'),
                $propotion
            )[0];
    }
    $pos = strpos($file_dir,'/wp-content');
    $file_dir = substr($file_dir, $pos);
    $file_dir = str_replace('/public_html','', $file_dir );

    if (strpos($file_dir,'WP_Error')) {
        echo 'WP_Error';
    } else if ($thumb){
        $result = [];
        $result[0] = $attachment_id;
        $result[1] = $file_dir;
        echo json_encode($result);
    } else {
        echo $file_dir;
    }
}

/**
 * @param $path
 * @return array
 */
function listdir_by_date($path){
    $dir = opendir($path);
    $list = array();
    while($file = readdir($dir)){
        if ($file !== '.' && $file !== '..'){
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
 * @param $find_file_name
 * @param $path
 * @param $orientation
 * @param $width
 * @param $proportion
 * @return mixed
 */
function getFileNameWithSize($find_file_name,$path,$orientation,$width,$proportion){
    //$path = '/home/i/investk2/otdix-na-altai.ru/public_html/wp-content/uploads/2016/10';
    $names = listdir_by_date($path);
    $i = 0;
    $res = $find_file_name;
    $cut_height = '';
    $ar = [];
    foreach ( $names as $name){
        $s = strpos($name,$find_file_name);//проверяем имя картинки
        if($s || $s === 0){ // если имя картинки совпало с искомым
            $cut_height = str_replace($find_file_name . 'x', '' , $name );
            if($orientation === 'horizontal' && (int)$width > (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
            } else if($orientation === 'vertical' && (int)$width < (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
            } else if($orientation === 'square' && (int)$width === (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
            } else {
                continue;
            }
        }
        if ($i>20) {
            break;
        }
        $i++;
    }
    return findNearestProportion($ar,$proportion);
}

/**
 * ищет подходящую по пропорциям картинку
 * @param $ar
 * @param $proportion
 * @return mixed
 */
function findNearestProportion($ar,$proportion){
    $count = count($ar);
    $max = $ar[0];
    for ($i = 1; $i < $count; $i++){
        if(abs($ar[$i][1] - $proportion) < abs($max[1]-$proportion)){
            $max = $ar[$i];
        }
    }
    return $max;
}

/**
 * @param $search
 * @return string
 */
function _getFileNameAgi($search)
{
    $converter = [
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',    'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        '.' => "_",   ' ' => "_",   ',' => '_',
        '?' => "_",   '!' => "_",   "'" => '',
        ':' => "_",   '(' => '',   ')' => '',
        '-' => '_',   ';' => '_',  '[' => '_',
        ']' => '_',   '{' => '_',  '}' => '_'
    ];
    $search = str_replace('"','',$search);
    $search = strtr(trim($search), $converter);
    $upload_dir = wp_upload_dir();
    $number = 0;
    if ($handle = opendir($upload_dir['path'])) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry === '.' || $entry === '..'){
                continue;
            }
            if (strpos($entry . '_', $search) === 0) {
                $num = (int) substr($entry, strlen($search) + 1);
                if ($num > $number) {
                    $number = $num;
                }
            }
        }
        closedir($handle);
    }
    return $search . '_' . ($number + 1);
}