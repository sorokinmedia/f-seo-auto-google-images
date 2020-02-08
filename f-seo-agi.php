<?php
/*
Plugin Name: F-Seo Auto Google Images
Description: Плагин автоматической подгрузки картинок из Google Images в текстовый редактор WordPress, FAQ
Author: F-Seo
Version: 2.4.1
Author URI: http://f-seo.ru/
*/

define ( 'FSEO_AGI_CURRENT_VERSION',  '2.4.1' );

include(dirname(__FILE__).'/AgiGoogleImage.php');


// Подключаем JS скрипт
function fseo_agi_init(){
    wp_enqueue_style('agi_style', plugins_url('css/agi_style.css', __FILE__), null, FSEO_AGI_CURRENT_VERSION, 'all');
    wp_enqueue_script('agi_script', plugins_url( 'js/agi_script.js', __FILE__ ), false, FSEO_AGI_CURRENT_VERSION);
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

//  настройки
function register_agi_settings(){
    //register_setting( 'agi_settings-group', 'agi_img_width' );
    register_setting( 'agi_settings-group', 'agi_img_churl' );
}

function sb_admin_fseo_agi_sett(){
    ?>
        <h1>Настройки F-Seo-Auto-Google-Images</h1>
    <?php
    //var_dump($_POST['agi_img_width']);
    if($_SERVER['REQUEST_METHOD']=='POST') {
        /*if ($_POST['agi_img_width'] != get_option('agi_img_width') && $_POST['agi_img_width']) update_option('agi_img_width', $_POST['agi_img_width']);
        if ($_POST['agi_img_big_width'] != get_option('agi_img_big_width') && $_POST['agi_img_width']) update_option('agi_img_big_width', $_POST['agi_img_big_width']);
        */
        if ($_POST['agi_img_churl'] != get_option('agi_img_churl')) update_option('agi_img_churl', $_POST['agi_img_churl']);
    }
    settings_fields( 'fseo-csv-settings-group' );
    ?>
    <form method="post">
        <!--<label class="clear d_block">Ширина картинок слева-справа</label>
        <select name="agi_img_width" id="agi_img_width" class="clear d_block">
            <option value="300" <?php /*if(get_option('agi_img_width') == 300) echo 'selected="selected"';*/?>>300</option>
            <option value="400" <?php /*if(get_option('agi_img_width') == 400) echo 'selected="selected"';*/?>>400</option>
        </select>
        <label class="clear d_block">Ширина больших картинок</label>
        <select name="agi_img_big_width" id="agi_img_big_width" class="clear d_block">
            <option value="600" <?php /*if(get_option('agi_img_big_width') == 600) echo 'selected="selected"';*/?>>600</option>
            <option value="700" <?php /*if(get_option('agi_img_big_width') == 700) echo 'selected="selected"';*/?>>700</option>
            <option value="750" <?php /*if(get_option('agi_img_big_width') == 750) echo 'selected="selected"';*/?>>750</option>
        </select>-->
        <label class="clear d_block">Убрать "плохие картинки" из выдачи (замедлит поиск в 3 раза)</label>
        <input name="agi_img_churl" class="clear d_block" id="agi_img_churl" type="checkbox" <?php if(get_option('agi_img_churl')) echo 'checked="checked"'; ?>  />
        <p class="submit"><input type="submit" class="button-primary" value="Сохранить" /></p> 
    </form>
    <?php
}

function get_agi_img_width_option(){
    $options = array();

    if(get_option('medium_size_w') &&  get_option('large_size_w')){
        $options[0] = get_option('medium_size_w');
        $options[1] = get_option('large_size_w');
        $options[2] = get_option('thumbnail_size_w');
    }
    else{
        $options[0] = 300;
        $options[1] = 600;
        $options[2] = 250;
    }
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


/*
 * Функционал поиска и загрузки картинок
 */

/*
 * Ф-я непосредственно поиска. Вызывается на фронте ajax запросом, возвращает распарсенную выдачу Google
 */
function agi_google_images_search()
{

    /* Параметры запроса */
    $params = array(
        "safe" => $_POST['safety'],
        "q" => urlencode($_POST['q']),
        //"start" => $_POST['page']*12,
        "ijn" => $_POST['page'],
        "sa" => "X",
        "search_orient" => $_POST['search_orient']
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

    if($_POST['search_orient'] == 'square') $tbs .= ",iar:s";

    $tbs = trim($tbs, ", ");
    if (!empty($tbs)) $params['tbs'] = $tbs;

    $url = "https://www.google.com/search?as_st=y&tbm=isch&sa=1";

    foreach ($params as $key => $value)
    {
        $url .= "&".$key."=".$value;
    }


    /* Запрос */
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Referer' => 'https://www.google.com/',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
        ),
    ));

    /* Парсим ответ */
    preg_match_all(
            "/<div .* class=\"rg_meta.*\".*>(.*)<\/div>/U",
            $response['body'], $matches
    );
    $items = array();

    $pos = strpos( $response['body'], "AF_initDataCallback({key: 'ds:2");
    $part1 = substr($response['body'], 0, $pos);
    $part2 = substr($response['body'], $pos);
    $posEnd = strpos($part2, '</script');
    $resStr = substr($part2, 0, $posEnd);

    /*echo json_encode($matches);
        die();*/

    foreach ($matches[1] as $number => $match)
    {
        //if (count($items) > 13) break;
        $item = array();
        /*$match = substr($match, 8);
        foreach(explode("&amp;", $match) as $pair)
        {
            list($key, $value) = explode("=", $pair, 2);
            $item[$key] = $value;
        }
        $item['imgurl']=$item['mages.google.com/imgres?imgurl'];

        if(get_option('agi_img_churl')){
            $check = fopen($item['imgurl'],"r");if($check) $item['churl'] = 'norm';else $item['churl'] = 'b9ka'; fclose($check);
        }*/

        $data = json_decode($match);
        $item['thumbnail'] = $data->tu;
        $item['imgurl'] = $data->ou;
        $item['id'] = $data->id;
        $item['w'] = $data->ow;
        $item['h'] = $data->oh;
        $item['imgrefurl'] = $data->isu;

        $items[] = $item;
    }

    echo json_encode([$items, $resStr]);
    die();
}
add_action('wp_ajax_agi_google_images_search', 'agi_google_images_search' );


/* Загрузка каринок на сайт - вызывается на фронте ajax запросом */
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
    if($width) {
        $file_dir = $upload_dir['path']."/"
            .getFileNameWithSize(
                $short_name . '-' . get_option($width . '_size_w'),
                $upload_dir['path'],
                $orientation,
                get_option($width . '_size_w'),
                $propotion
            )[0];
    }

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

    die();
}
add_action('wp_ajax_agi_google_images_upload', 'agi_google_images_upload' );

/* Сортировка файлов по дате */
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

/* Получение имя файла, подходящего по пропорциям */
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

            if(
                    ($orientation == 'horizontal' && (int)$width > (int)$cut_height)
                    ||($orientation == 'vertical' && (int)$width < (int)$cut_height)
                    ||($orientation == 'square' && (int)$width == (int)$cut_height)

            ) {
                $ar[] = array($name,(int)$width/(int)$cut_height);

            } else continue;
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

/* Строит имя файла на основе поискового запроса */
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
        ']' => '_',   '{' => '_',  '}' => '_',
        '/'=>'_',     '«' => '',   '»' => ''
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


