<?php

/**
 * Class AgiGoogleImage
 *
 * @property $mime
 * @property $filename
 * @property $width
 * @property $height
 * @property $files
 */
class AgiGoogleImage
{
    public $mime = false; 
    public $filename;
    public $width;
    public $height;
    public $files;

    protected $_gd_handle;

    /**
     * AgiGoogleImage constructor.
     * @param $url
     * @param $referer
     * @throws Exception
     */
    public function __construct($url, $referer)
    {
        //если есть пробелы, декодим
        if (strrpos($url,'252020') || strrpos($url,'252520')) {
            $url = urldecode($url);
        }
        $response = wp_remote_get($url, [
            'headers' =>[
                'Referer' => $referer,
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
            ],
        ]);
        $mime = isset($response['headers']['content-type']) ? $response['headers']['content-type'] : null;
        switch ($mime)
        {
            case 'image/jpeg':
            case 'image/pjpeg':
            case 'image/png':
            case 'image/gif':
                $this->mime = $mime;
                break;
            default:
                throw new Exception('AgiGoogleImage::__construct() unsupported mime type: ' . $mime);
        }
        $this->filename = sys_get_temp_dir() . '/' . time() . mt_rand(0, 99999);
        file_put_contents($this->filename, $response['body']);
        $this->files = $response['body'];
        if (is_string($response['body'])) {
            $this->_gd_handle = imagecreatefromstring($response['body']);
        } else {
             // Скорее всего, сюда мы никогда не попадем, т.к. $response['body'] - это строка
            switch ($mime)
            {
                case 'image/jpeg':
                    // Но если все-таки попадем, то эта ветка предполагает тип "ссылка на ресурс" (resource)
                    $this->_gd_handle = $response['body'];
                    break;
                case 'image/pjpeg':
                    $this->_gd_handle = imagecreatefromjpeg($this->files); // А эта ветка - что $this->files ($response['body']) - это имя файла изображения
                    break;
                case 'image/png':
                    $this->_gd_handle = imagecreatefrompng($this->files);
                    break;
                case 'image/gif':
                    $this->_gd_handle = imagecreatefromgif($this->files);
                    break;
                default:
                    throw new Exception('AgiGoogleImage::__construct() - unsupported mime type: ' . $mime);
            }
        }
        $this->width = imagesx($this->_gd_handle);
        $this->height = imagesy($this->_gd_handle);
    }

    /**
     * уничтожение объекта
     */
    public function __destruct()
    {
        imagedestroy($this->_gd_handle);
        unlink($this->filename);
    }

    /**
     * отправка в браузер
     */
    public function sendToBrowser()
    {
        header('Content-Type: ' . $this->mime);
        echo file_get_contents($this->filename);
    }

    /**
     * сжатие изображения
     * @param int $ratio
     */
    public function compress($ratio = 80)
    {
        imagejpeg($this->_gd_handle, $this->filename, $ratio);
    }
}