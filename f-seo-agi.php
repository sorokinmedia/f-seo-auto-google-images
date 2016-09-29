<?php
/*
Plugin Name: F-Seo Auto Google Images
Description: Плагин автоматической подгрузки картинок из Google Images в текстовый редактор WordPress, FAQ
Author: F-Seo
Version: 1.0
Author URI: http://f-seo.ru/
*/

define ( 'FSEO_AGI_CURRENT_VERSION',  '1.0' );

include(dirname(__FILE__).'/GoogleImage.php');


// Подключаем JS скрипт
function fseo_agi_init(){
    wp_enqueue_style('agi_style', plugins_url('css/agi_style.css', __FILE__), null, FSEO_AGI_CURRENT_VERSION, 'all');
    wp_enqueue_script('agi_script', plugins_url( 'js/agi_script.js', __FILE__ ), false, FSEO_AGI_CURRENT_VERSION);
    //echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
}
add_action( 'admin_init', 'fseo_agi_init' );


// страница администрирования
function fseo_agi_setup_menu() {
    // верхний уровень
    add_menu_page('F-Seo Auto Google Images', 'F-Seo Auto Google Images', 'manage_option', 'fseo_agi_settings_page', 'sb_admin_fseo_agi');
    // подуровни
    add_submenu_page( 'fseo_agi_settings_page', 'Настройки', 'Настройки', 'manage_options', 'f-seo-agi',  'sb_admin_fseo_agi_sett');

    add_action( 'admin_init', 'register_agi_settings' );
}
add_action('admin_menu', 'fseo_agi_setup_menu');


function register_agi_settings(){
    register_setting( 'agi_settings-group', 'agi_img_width' );
}

function sb_admin_fseo_agi_sett(){
    ?>
        <h1>Настройки F-Seo-Auto-Google-Images</h1>
    <?php
    //var_dump($_POST['agi_img_width']);
    if($_POST['agi_img_width'] != get_option('agi_img_width') && $_POST['agi_img_width']) update_option( 'agi_img_width', $_POST['agi_img_width']);
    if($_POST['agi_img_big_width'] != get_option('agi_img_big_width') && $_POST['agi_img_width']) update_option( 'agi_img_big_width', $_POST['agi_img_big_width']);
    settings_fields( 'fseo-csv-settings-group' );
    ?>
    <form method="post">
        <label>Ширина картинок слева-справа</label>
        <select name="agi_img_width" id="agi_img_width">
            <option value="300" <?php if(get_option('agi_img_width') == 300) echo 'selected="selected"';?>>300</option>
            <option value="400" <?php if(get_option('agi_img_width') == 400) echo 'selected="selected"';?>>400</option>
        </select>
        <label>Ширина больших картинок</label>
        <select name="agi_img_big_width" id="agi_img_big_width">
            <option value="600" <?php if(get_option('agi_img_big_width') == 600) echo 'selected="selected"';?>>600</option>
            <option value="700" <?php if(get_option('agi_img_big_width') == 700) echo 'selected="selected"';?>>700</option>
            <option value="750" <?php if(get_option('agi_img_big_width') == 750) echo 'selected="selected"';?>>750</option>
        </select>
        <p class="submit"><input type="submit" class="button-primary" value="Сохранить" /></p>
    </form>
    <?php
}

function get_agi_img_width_option(){
    $options = array();
    $options[0] = get_option('agi_img_width');
    $options[1] = get_option('agi_img_big_width');
    echo json_encode($options);
    die();
}
add_action('wp_ajax_get_agi_img_width_option', 'get_agi_img_width_option' );

function agi_admin_head(){
    $id = (int) get_the_ID();
    if ($id) {
        echo "<script type=\"text/javascript\"> var googleImagesPostId = ".$id.";</script>";
    }
}
add_action('admin_head','agi_admin_head');


function google_images_search()
{

    $params = array(
        "safe" => $_POST['safety'],
        "q" => urlencode($_POST['q']),
        "start" => $_POST['page']*20,
        "sa" => "X",
    );
    $tbs = "";
    switch ($_POST['size'])
    {
        case "i":
        case "m":
        case "l":
            $tbs .= ",isz:".$_POST['size'];
            break;
        case "":
            break;
        default:
            $tbs .= ",isz:lt,islt:".$_POST['size'];
            break;
    }

    switch ($_POST['color'])
    {
        case "":
            break;
        case "color":
            $tbs .= ",ic:color";
            break;
        case "nocolor":
            $tbs .= ",ic:gray";
            break;
        case "trans":
            $tbs .= ",ic:trans";
            break;
        default:
            $tbs .= ",ic:specific,isc:".$_POST['color'];
            break;
    }

    if (!empty($_POST['type']))
    {
        $tbs .= ",itp:".$_POST['type'];
    }

    if (!empty($_POST['period']))
    {
        $tbs .= ",qdr:".$_POST['period'];
    }

    $tbs .= ",iar:w"; 

    $tbs = trim($tbs, ", ");
    if (!empty($tbs)) $params['tbs'] = $tbs;

    $url = "https://www.google.com/search?tbm=isch&ijn=3";

    foreach ($params as $key => $value)
    {
        $url .= "&".$key."=".$value;
    }
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Referer' => $referer,
            'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
        ),
    ));
    preg_match_all("/<div class=\"rg_di.*\".*>.*<a href=\"(.*)\".*<img.*data-src=\"(.*)\".*<\/a>/U", $response['body'], $matches);
    $items = array();
    foreach ($matches[1] as $number => $match)
    {
        if (count($items) >= 14) break;
        $item = array();
        $match = substr($match, 8);
        foreach(explode("&amp;", $match) as $pair)
        {
            list($key, $value) = explode("=", $pair, 2);
            $item[$key] = $value;
        }
        $item['imgurl']=$item['mages.google.com/imgres?imgurl'];
        $item['thumbnail'] = $matches[2][$number];
        $items[] = $item;
    }

    echo json_encode($items);
    die();
}
add_action('wp_ajax_google_images_search', 'google_images_search' );

function google_images_upload()
{
    $url = $_POST['url'];
    $referer = $_POST['referer'];
    $post_id = $_POST['post_id'];
    $search = $_POST['search'];


    try
    {
        $image = new GoogleImage($url, $referer);
    }
    catch(Exception $e)
    {
        header("HTTP/1.0 404 Not Found");
        die();
    }
    if ($image->mime=='image/png')
    {
        $filename = _getFileName($search).date("_dHis").".png";
    } else {
        $filename = _getFileName($search).date("_dHis").".jpg";
    }

    //echo 123;//$image->mime . '   ' . $image->filename . '    ' . $image->files . '    ' . $image->width;

    $upload_dir = wp_upload_dir();

    $image->compress('');
    $upload_image=wp_upload_bits($filename,null,$image->files);

    $attachment = array(
        'guid' => $upload_image['url'],
        'post_mime_type' => $image->mime,
        'post_title' => (get_option('gi_search_title') == "" ? "" : $this->_mb_ucfirst($_POST['search'])),
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

    $pos = strpos($file_dir,'/wp-content');
    $file_dir = substr($file_dir, $pos); 
    $file_dir = str_replace('/public_html','',$file_dir );
    echo $file_dir;

    die();
}
add_action('wp_ajax_google_images_upload', 'google_images_upload' );

function _getFileName($search)
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
    );
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

