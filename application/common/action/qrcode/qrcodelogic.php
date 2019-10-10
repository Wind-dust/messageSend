<?php
namespace app\common\action\qrcode;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

class qrcodelogic
{
    public $qrCode;
    public $qrCodeIm;
    public $qrCodeContent;
    public $qrCode_size;
    public $qrCode_title;
    public $outputContent;

    public function __construct($data, $size = 300, $qrCode_title = 'qrcode')
    {
        $this->qrCode_title = $qrCode_title;
        if ($this->getImagetype($data)) {
            $this->qrCodeContent = $data;
            $this->qrCode_size = imagesx($this->qrCodeIm); //二维码图片宽度
        } else {
            $this->qrCode = new QrCode($data);
            $this->qrCode->setSize($size);
            $this->qrCode->setMargin(0);
            $this->qrCode->setEncoding('UTF-8');
            // $this->qrCode->setForegroundColor(['r' => 12, 'g' => 12, 'b' =>12, 'a' => 0]);
            // $this->qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
            // $this->qrCode->setLogoPath(SYSTEM_ROOT_PATH . '/data/logo.png');
            // $this->qrCode->setLogoWidth(100);


            $this->qrCode_size = $size;
            $this->qrCodeContent = $this->outputContent = $this->qrCode->writeString();
            $this->qrCodeIm = imagecreatefromstring($this->qrCodeContent);
        }
    }

    public function __destruct()
    {
        if ($this->qrCodeIm) {
            imagedestroy($this->qrCodeIm);
        }
        unset($this->qrCode, $this->qrCodeContent, $this->qrCode_size, $this->outputContent);
    }

    public function getImagetype($imagebin)
    {
        $strInfo = @unpack('C2chars', $imagebin);
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        $fileType = '';
        switch ($typeCode) {
            case 255216:
                $fileType = 'jpeg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = false;
        }
        return $fileType;
    }

    public function coverbackground($coverbg, $x, $y)
    {

        $fp = fopen($coverbg, 'rb');
        $coverbg_bin = fread($fp, 2); //只读2字节
        fclose($fp);
        if ($coverbg_type = $this->getImagetype($coverbg_bin)) {

            $imfunc = 'imagecreatefrom' . $coverbg_type;
            $cbim = $imfunc($coverbg);

            imagecopymerge($cbim, $this->qrCodeIm, $x, $y, 0, 0, $this->qrCode_size, $this->qrCode_size, 100);

            ob_start();
            imagejpeg($cbim, null, 100);
            $this->outputContent = ob_get_contents();
            ob_end_clean();
            imagedestroy($cbim);
        }

    }

    //加载原生数据
    public function getoutput()
    {
        return $this->outputContent;
    }
    //加载原生数据
    public function output($file = '')
    {
        if ($file) {
            $fp = fopen($file, 'w');
            fwrite($fp, $this->outputContent());
            fclose($fp);
        } else {
            header("Content-Disposition: inline; filename=" . $this->qrCode_title . ".png");
            header('Content-Type: ' . $this->qrCode->getContentType());
            echo $this->outputContent;
        }
    }

}
