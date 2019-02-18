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
        //todo: wtf?
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
        switch ($mime)
        {
            case 'image/jpeg':
                $this->_gd_handle = $response['body'];
                break;
            case 'image/pjpeg':
                $this->_gd_handle = imagecreatefromjpeg($this->files);
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
        $this->width = imagesx($this->_gd_handle);
        $this->height = imagesy($this->_gd_handle);
    }

    public function __destruct()
    {
        imagedestroy($this->_gd_handle);
        unlink($this->filename);
    }

    public function sendToBrowser()
    {
        header('Content-Type: '.$this->mime);
        echo file_get_contents($this->filename);
    }

    public function compress($ratio = 80)
    {
        imagejpeg($this->_gd_handle, $this->filename, $ratio);
    }

    public function resize($newWidth, $newHeight)
    {
        if ($this->width > $this->height)
        {
            $width = $newWidth;
            $height = $this->height * ($newHeight/$this->width);
        }
        if ($this->width < $this->height)
        {
            $width = $this->width * ($newWidth/$this->height);
            $height = $newHeight;
        }
        if ($this->width === $this->height)
        {
            $width = $newWidth;
            $height = $newHeight;
        }
        $gd_handle = imagecreatetruecolor($width, $height);
        imagecopyresampled($gd_handle, $this->_gd_handle, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
        imagedestroy($this->_gd_handle);
        $this->_gd_handle = $gd_handle;
        $this->mime = 'image/jpg';
        imagejpeg($this->_gd_handle, $this->filename, 80);
    }
}