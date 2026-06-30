<?php


namespace app\common\lib;
use think\facade\Env;
use think\File;

class MyPdf
{
    public static function createPdf($html,$filename){
        $htmlPath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.Tools::create_guid().'.html';
        $pdfPath = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.Tools::create_guid().'.pdf';
        $cmd = config('pdf.cmdPath');
        ob_start();
        file_put_contents($htmlPath,$html);
        ob_end_clean();
        try{
            $output = shell_exec($cmd." -B 0 -L 0 -R 0 -T 0 ".$htmlPath." ".$pdfPath);
            var_dump($output);
        }catch(Exception $e){

        }
        $content = '';
        if(file_exists($pdfPath)){
            $content = file_get_contents($pdfPath);
        }else{
            $content = "error";
        }
        unlink($htmlPath);
        unlink($pdfPath);
        return $content;
    }
}