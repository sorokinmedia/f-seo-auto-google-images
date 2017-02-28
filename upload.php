<?php

require_once('../../../wp-load.php');

agi_google_images_upload();

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

    try
    {
        $image = new AgiGoogleImage($url, $referer);
    }
    catch(Exception $e)
    {
        header("HTTP/1.0 404 Not Found");
        die();
    }
    if ($image->mime=='image/png')
    {
        $filename = _getFileNameAgi($search).date("_dHis").".png";
    } else {
        $filename = _getFileNameAgi($search).date("_dHis").".jpg";
    }

    $upload_dir = wp_upload_dir();

    $image->compress('');
    $upload_image=wp_upload_bits($filename,null,$image->files);

    $attachment = array(
        'guid' => $upload_image['url'],
        'post_mime_type' => $image->mime,
        'post_title' => '',//(get_option('gi_search_title') == "" ? "" : $this->_mb_ucfirst($_POST['search'])),
        'post_content' => '',
        'post_status' => 'inherit',
    );

    $file_dir = $upload_dir['path']."/".$filename;

    $attachment_id = wp_insert_attachment( $attachment, $image_url, $post_id );
    if( !function_exists( 'wp_generate_attachment_data' ) )
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload_dir['path']."/".$filename );
    wp_update_attachment_metadata( $attachment_id, $attach_data );
    update_attached_file( $attachment_id, $upload_dir['path']."/".$filename);

    $short_name = '';
    if(strpos($filename, '.jpg'))$short_name = str_replace('.jpg','' ,$filename );
    if(strpos($filename, '.png'))$short_name = str_replace('.png','' ,$filename );
    if(strpos($filename, '.gif'))$short_name = str_replace('.gif','' ,$filename );

    if($thumb) {
        update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
        $file_dir = $upload_dir['path']."/".getFileNameWithSize($short_name . '-' . get_option('medium_size_w'),$upload_dir['path'],$orientation,get_option('medium_size_w'),$propotion)[0];
    }
    if($width) $file_dir = $upload_dir['path']."/".getFileNameWithSize($short_name . '-' . get_option($width . '_size_w'),$upload_dir['path'],$orientation,get_option($width . '_size_w'),$propotion)[0];

    $pos = strpos($file_dir,'/wp-content');
    $file_dir = substr($file_dir, $pos);
    $file_dir = str_replace('/public_html','',$file_dir );

    if (strpos($file_dir,'WP_Error')) echo 'WP_Error';

    else if($thumb) {
        $result = array();
        $result[0] = $attachment_id;
        $result[1] = $file_dir;
        echo json_encode($result);
    }

    else echo $file_dir;
    //echo 'post_ID ' . $post_id;

}
//add_action('wp_ajax_agi_google_images_upload', 'agi_google_images_upload' );

function listdir_by_date($path){
    $dir = opendir($path);
    $list = array();
    while($file = readdir($dir)){
        if ($file != '.' and $file != '..'){
            // кроме даты создания файлы добавляем ещё и имя
            // чтобы удостоверится, что мы не заменяем ключ массива
            // $ctime = filectime($data_path . $file) . ',' . $file;
            // UPD:
            $ctime = filectime($path . '/' . $file) . ',' . $file;
            $list[$ctime] = $file;
        }
    }
    closedir($dir);
    krsort($list);
    return $list;
}

function getFileNameWithSize($find_file_name,$path,$orientation,$width,$proportion){
    //$path = '/home/i/investk2/otdix-na-altai.ru/public_html/wp-content/uploads/2016/10';
    $names = listdir_by_date($path);
    $i = 0;
    $res = $find_file_name;
    $cut_height = '';
    $ar = array();
    foreach ( $names as $name){
        $s = strpos($name,$find_file_name);//проверяем имя картинки
        if($s || $s === 0){ // если имя картинки совпало с искомым
            $cut_height = str_replace($find_file_name . 'x', '' , $name );
            if($orientation == 'horizontal' && (int)$width > (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
                /*$res = $name;
                break;*/
            }
            else if($orientation == 'vertical' && (int)$width < (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
                /*$res = $name;
                break;*/
            }
            else if($orientation == 'square' && (int)$width == (int)$cut_height ){
                $ar[] = array($name,(int)$width/(int)$cut_height);
                /*$res = $name;
                break;*/
            }
            else continue;
        }
        if($i>20) break;
        $i++;
    }

    return findNearestProportion($ar,$proportion);
}

//ищет подходящую по пропорциям картинку
function findNearestProportion($ar,$proportion){
    $max = $ar[0];
    for($i=1; $i<count($ar); $i++){
        if(abs($ar[$i][1] - $proportion)<abs($max[1]-$proportion)){
            $max = $ar[$i];
        }
    }
    return $max;
}

function _getFileNameAgi($search)
{

    $converter = array(
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
    );
    $search = str_replace('"','',$search);
    $search = strtr(trim($search), $converter);
    $upload_dir = wp_upload_dir();

    $number = 0;
    if ($handle = opendir($upload_dir['path']))
    {
        while (false !== ($entry = readdir($handle)))
        {
            if ($entry == "." || $entry == "..") continue;

            if (strpos($entry."_", $search, 0) === 0)
            {
                $num = (int) substr($entry, strlen($search) + 1);
                if ($num > $number) $number = $num;
            }
        }

        closedir($handle);
    }
    return $search."_".($number+1);
}