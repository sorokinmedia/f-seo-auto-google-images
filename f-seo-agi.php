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

    add_action( 'admin_init', 'register_fseo_csv_settings' );
}
add_action('admin_menu', 'fseo_agi_setup_menu');


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
        $filename = $this->_getFileName($search).date("_dHis").".png";
    } else {
        $filename = $this->_getFileName($search).date("_dHis").".jpg";
    }

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
    $attachment_id = wp_insert_attachment( $attachment, $image_url, $post_id );
    if( !function_exists( 'wp_generate_attachment_data' ) )
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload_dir['path']."/".$filename );
    echo $attach_data;
    wp_update_attachment_metadata( $attachment_id, $attach_data );
    update_attached_file( $attachment_id, $upload_dir['path']."/".$filename);

    die();
}
add_action('wp_ajax_google_images_upload', 'google_images_upload' ); 

