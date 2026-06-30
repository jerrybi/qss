<?php


namespace app\common\lib;
use think\facade\env;

class QRCode
{
    public static function create_qrcode($data,$logo,$name=null){
        header('Content-Type:image/png');
        if(!empty($name)){
            $filename = $name;
        }else{
            $filename = date('YmdHis') . '_' . rand(1000, 9999).'.jpg';
        }
        $outfile = Env::get('root_path')."/public/qrcode/".$filename;
        $level = 'L';
        $size = 6;
        ob_start();
        \PHPQRCode\QRcode::png($data,$outfile,$level,$size,2);
        if(empty($logo)){
//            $logoPath = Env::get('root_path').'/public/static/images/logo.jpg';
            $logoPath = FALSE;
        }else{
            $logoPath = $logo;
        }
        $QR = $outfile;
        if($logoPath !== FALSE){
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logoPath = imagecreatefromstring(file_get_contents($logoPath));
            $QR_width = imagesx($QR);
            $QR_height = imagesy($QR);
            $logo_width = imagesx($logoPath);
            $logo_height = imagesy($logoPath);
            $logo_qr_width = $QR_width/5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width-$logo_qr_width)/2;
            imagecopyresampled($QR,$logoPath,$from_width,$from_width,0,0,$logo_qr_width,$logo_qr_height,$logo_width,$logo_height);
            imagepng($QR,$outfile);
        }
        return $filename;
    }
}