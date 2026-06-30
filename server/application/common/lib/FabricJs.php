<?php


namespace app\common\lib;
use Imagick;
use ImagickPixel;
use ImagickDraw;
use think\facade\Env;

class FabricJs
{
    protected $fnt = [];
    public function __construct()
    {
        $this->initFonts();    
    }
    
    public function initFonts(){
        $this->fnt['Helvetica'] = "Helvetica.ttf";
        $this->fnt['Arial'] = "arial.ttf";
        $this->fnt['Arial Black'] = "ariblk.ttf";
        $this->fnt['Verdana'] = "verdana.ttf";
        $this->fnt['Tahoma'] = "tahoma.ttf";
        $this->fnt['Trebuchet MS'] = "trebuc.ttf";
        $this->fnt['Impact'] = "impact.ttf";
        $this->fnt['Gill Sans'] = "GILSANUB.ttf";
        $this->fnt['Times New Roman'] = "times.ttf";
        $this->fnt['Georgia'] = "georgia.ttf";
        $this->fnt['Palatino'] = "pala.ttf";
        $this->fnt['Baskerville'] = "BASKVILL.ttf";
        $this->fnt['Courier'] = "cour.ttf";
        $this->fnt['Lucida'] = "LSANS.ttf";
        $this->fnt['Monaco'] = "Monaco.ttf";
        $this->fnt['Bradley Hand'] = "BRADHITC.ttf";
        $this->fnt['Brush Script MT'] = "BRUSHSCI.ttf";
        $this->fnt['Luminari'] = "Luminari.ttf";
        $this->fnt['Comic Sans MS'] = "comic.ttf";
        $this->fnt['Abril Fatface'] = "AbrilFatface.ttf";
        $this->fnt['Alfa Slab One'] = "AlfaSlabOne.ttf";
        $this->fnt['Bebas Neue'] = "BebasNeue.ttf";
        $this->fnt['Caveat'] = "Caveat.ttf";
        $this->fnt['Comfortaa'] = "Comfortaa.ttf";
        $this->fnt['Dancing Script'] = "Dancing Script.ttf";
        $this->fnt['IBM Plex Mono'] = "IBMPlexMono.ttf";
        $this->fnt['Inconsolata'] = "Inconsolata.ttf";
        $this->fnt['Indie Flower'] = "IndieFlower.ttf";
        $this->fnt['Lato'] = "Lato.ttf";
        $this->fnt['Lobster'] = "Lobster.ttf";
        $this->fnt['Lora'] = "Lora.ttf";
        $this->fnt['Merriweather'] = "Merriweather.ttf";
        $this->fnt['Montserrat'] = "Montserrat.ttf";
        $this->fnt['Open Sans'] = "OpenSans.ttf";
        $this->fnt['Pacifico'] = "Pacifico.ttf";
        $this->fnt['Playfair Display'] = "PlayfairDisplay.ttf";
        $this->fnt['Poppins'] = "Poppins.ttf";
        $this->fnt['PT Serif'] = "PTSerif.ttf";
        $this->fnt['Roboto'] = "Roboto.ttf";
        $this->fnt['Roboto Mono'] = "RobotoMono.ttf";
        $this->fnt['Roboto Slab'] = "RobotoSlab.ttf";
        $this->fnt['Shadows Into Light'] = "ShadowsIntoLight.ttf";
        $this->fnt['Source Code Pro'] = "SourceCodePro.ttf";
        $this->fnt['Space Mono'] = "SpaceMono.ttf";
    }
    
    public function toPNG($json,$width,$height,$filename){
        $printData = json_decode($json);
        try {
            $print = new Imagick();
            $print->setResolution(300, 300);
            $background = (empty($printData->background)) ? 'transparent' : $printData->background;
            $print->newImage($width, $height, new ImagickPixel($background));

            $print->setImageMatte(TRUE);
            $print->setImageFormat('png32');
            $print->setImageUnits(Imagick::RESOLUTION_PIXELSPERCENTIMETER);

            $imageObjects = $textObjects = $clueTextObjects = [];

            foreach ($printData->objects as $object) {
                if ($object->type == 'image') {
                    $imageObjects[] = $object;
                } else if ($object->type == 'text') {
                    $textObjects[] = $object;
                } else if ($object->type == 'ClueTextBox') {
                    $clueTextObjects[] = $object;
                }
            }
            foreach ($imageObjects as $object) {
                $this->addImageToLarge($object, $print, $printData);
            }

            foreach ($textObjects as $object) {
                $this->addTextToLarge($object, $print, $printData);
            }

            foreach ($clueTextObjects as $object) {
                $this->addClueTextToLarge($object, $print, $printData);
            }

            $print->setImageFormat('png');
            file_put_contents($filename, $print);
            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();
            LogUtil::info("[toPNG]error:".$e->getMessage());
            return false;
        }
    }

    function addImageToLarge($object, $print, $printData) {
        try {
            $url = !empty($object->customText) ? $object->customText : $object->src;
            $image = file_get_contents($url);
            $src = new Imagick();
            $src->readImageBlob($image);
            $size = $src->getImageGeometry();
            $resizeWidth = $this->changeDpi($object->width * $object->scaleX);
            $resizeHeight = $this->changeDpi($object->height * $object->scaleY);

            $src->resizeImage($resizeWidth, $resizeHeight, Imagick::FILTER_LANCZOS, 1);
            $sizeAfterResize = $src->getImageGeometry();

            $src->rotateImage(new ImagickPixel('none'), $object->angle);
            $sizeAfterRotate = $src->getImageGeometry();

            $left = $object->left < 0 ? -1 * abs($this->changeDpi($object->left)) : $this->changeDpi($object->left);
            $top = $object->top < 0 ? -1 * abs($this->changeDpi($object->top)) : $this->changeDpi($object->top);

            $print->compositeImage($src, Imagick::COMPOSITE_OVER, $left, $top);
            $src->destroy();
        } catch (\Exception $e) {
            echo $e->getMessage();
            LogUtil::info("[addImageToLarge]error:".$e->getMessage());
            exit();
        }
    }

    function addTextToLarge($object, $print, $printData) {
        try {
            $line_height_ratio = $object->lineHeight;
            $resizeWidth = $this->changeDpi($object->width * $object->scaleX);
            $resizeHeight = $this->changeDpi($object->height * $object->scaleY);

            $print2 = new Imagick();
            $print2->setResolution(300, 300);
            $print2->newImage($resizeWidth, $resizeHeight, "transparent");
            $print2->setImageVirtualPixelMethod(imagick::VIRTUALPIXELMETHOD_BACKGROUND);
            $print2->setImageFormat('png32');
            $print2->setImageUnits(imagick::RESOLUTION_PIXELSPERCENTIMETER);

            // Instantiate Imagick utility objects
            $draw = new ImagickDraw();
            $color = new ImagickPixel($object->fill);

            //$starting_font_size = 100*1.33;
            $font_size = (($object->fontSize * $resizeWidth) / $object->width);

            $draw->setFontWeight(($object->fontWeight == 'bold') ? 600 : 100 );
            $draw->setFontStyle(0);
            $draw->setFillColor($color);

            // Load Font
            //$font_size = $starting_font_size;
            $fontPath = $path = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.$this->fnt[$object->fontFamily];
            $draw->setFont($fontPath);
            $draw->setFontSize($font_size);

            $draw->setTextAntialias(true);
            $draw->setGravity(Imagick::GRAVITY_CENTER);

            if ($object->stroke) {
                $draw->setStrokeColor($object->stroke);
                $draw->setStrokeWidth($object->strokeWidth);
                $draw->setStrokeAntialias(true);  //try with and without
            }

            $total_height = 0;

            // Run until we find a font size that doesn't exceed $max_height in pixels
            while (0 == $total_height || $total_height > $resizeHeight) {
                if ($total_height > 0) {
                    $font_size--; // we're still over height, decrement font size and try again
                }
                $draw->setFontSize($font_size);

                // Calculate number of lines / line height
                // Props users Sarke / BMiner: http://stackoverflow.com/questions/5746537/how-can-i-wrap-text-using-imagick-in-php-so-that-it-is-drawn-as-multiline-text
                $words = preg_split('%\s%', $object->text, -1, PREG_SPLIT_NO_EMPTY);
                $lines = array();
                $i = 0;
                $line_height = 0;

                while (count($words) > 0) {
                    $metrics = $print2->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
                    $line_height = max($metrics['textHeight'], $line_height);

                    if ($metrics['textWidth'] > $resizeWidth || count($words) < $i) {
                        $lines[] = implode(' ', array_slice($words, 0, --$i));
                        $words = array_slice($words, $i);
                        $i = 0;
                    }
                }

                $total_height = count($lines) * $line_height * $line_height_ratio;

                if ($total_height > 0) {

                }
            }
            // Writes text to image
            $x_pos = 0;
            $y_pos = 0;

            for ($i = 0; $i < count($lines); $i++) {
                $print2->annotateImage($draw, $x_pos, $y_pos + ($i * $line_height * $line_height_ratio), $object->angle, $lines[$i]);
            }

            if ($object->flipX == 1)
                $print2->flopImage(); // x
            if ($object->flipY == 1)
                $print2->flipImage(); // y

            $print2->trimImage(0);
            $print2->setImagePage(0, 0, 0, 0);

            $print2->resizeImage($resizeWidth, 0, Imagick::FILTER_CATROM, 0.9, false);

            $left = $object->left < 0 ? -1 * abs($this->changeDpi($object->left)) : $this->changeDpi($object->left);
            $top = $object->top < 0 ? -1 * abs($this->changeDpi($object->top)) : $this->changeDpi($object->top);

            $print->compositeImage($print2, Imagick::COMPOSITE_OVER, $left, $top);

            //header("Content-Type: image/png");
            //echo $print2;exit;
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit();
        }
    }

    function addClueTextToLarge($object, $print, $printData) {
        try {
            $line_height_ratio = $object->lineHeight;
            $resizeWidth = $this->changeDpi($object->width * $object->scaleX);
            $resizeHeight = $this->changeDpi($object->height * $object->scaleY);

            $print2 = new Imagick();
            $print2->setResolution(300, 300);
            $print2->newImage($resizeWidth, $resizeHeight, "transparent");
            $print2->setImageVirtualPixelMethod(imagick::VIRTUALPIXELMETHOD_BACKGROUND);
            $print2->setImageFormat('png32');
            $print2->setImageUnits(imagick::RESOLUTION_PIXELSPERCENTIMETER);

            // Instantiate Imagick utility objects
            $draw = new ImagickDraw();
            $color = new ImagickPixel($object->fill);

            //$starting_font_size = 100*1.33;
            $font_size = (($object->fontSize * $resizeWidth) / $object->width);

            $draw->setFontWeight(($object->fontWeight == 'bold') ? 600 : 100 );
            $draw->setFontStyle(0);
            $draw->setFillColor($color);

            // Load Font
            //$font_size = $starting_font_size;
            $fontPath = $path = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$this->fnt[$object->fontFamily];
//            $fontPath = $path = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'simsun.ttc';
            $draw->setFont($fontPath);
            $draw->setFontSize($font_size);
//            $draw->setTextEncoding('UTF-8');

            $draw->setTextAntialias(true);
//            $draw->setGravity(Imagick::GRAVITY_CENTER);
            $draw->setGravity(Imagick::GRAVITY_NORTHWEST);

            if ($object->stroke) {
                $draw->setStrokeColor($object->stroke);
                $draw->setStrokeWidth($object->strokeWidth);
                $draw->setStrokeAntialias(true);  //try with and without
            }

            $total_height = 0;
            $line_height = 0;
            $max_width = 0;

            // Run until we find a font size that doesn't exceed $max_height in pixels
            while (0 == $total_height || $total_height > $resizeHeight || $max_width > $resizeWidth) {
                if ($total_height > 0 || $max_width > 0) {
                    $font_size--; // we're still over height, decrement font size and try again
                }
                $draw->setFontSize($font_size);

                // Calculate number of lines / line height
                // Props users Sarke / BMiner: http://stackoverflow.com/questions/5746537/how-can-i-wrap-text-using-imagick-in-php-so-that-it-is-drawn-as-multiline-text
                $words = preg_split('%\s%', $object->text, -1, PREG_SPLIT_NO_EMPTY);
                $lines = array();
                $i = 0;
                $line_height = 0;
                $max_width = 0;

                while (count($words) > 0) {
                    $metrics = $print2->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
                    $line_height = max($metrics['textHeight'], $line_height);
                    if ($metrics['textWidth'] > $resizeWidth || count($words) < $i) {
                        if($i == 1){
                            if($metrics['textWidth'] > $resizeWidth){
                                $max_width = $metrics['textWidth'];
                                $words = [];
                            }else{
                                $lines[] = implode(' ', $words);
                                $words = [];
                                $max_width = 0;
                            }
                        }else{
                            $lines[] = implode(' ', array_slice($words, 0, --$i));
                            $words = array_slice($words, $i);
                            $max_width = 0;
                        }
                        $i = 0;
                    }
                }

                $total_height = count($lines) * $line_height * $line_height_ratio;

                if ($total_height > 0) {

                }
            }
            // Writes text to image
            $x_pos = 0;
            $y_pos = 0;
            if($object->customAlign == 'ml' || $object->customAlign == 'mc' || $object->customAlign == 'mr'){
                $y_pos = $resizeHeight >= $total_height ? ($resizeHeight - $total_height) / 2 : 0;
            }else if($object->customAlign == 'bl' || $object->customAlign == 'bc' || $object->customAlign == 'br'){
                $y_pos = $resizeHeight >= $total_height ? ($resizeHeight - $total_height) : 0;
            }

            for ($i = 0; $i < count($lines); $i++) {
                if($object->customAlign == 'tc' || $object->customAlign == 'mc' || $object->customAlign == 'bc'){
                    $metrics = $print2->queryFontMetrics($draw, $lines[$i]);
                    $x_pos = $resizeWidth >= $metrics['textWidth'] ? ($resizeWidth - $metrics['textWidth']) / 2 : 0;
                }else if($object->customAlign == 'tr' || $object->customAlign == 'mr' || $object->customAlign == 'br'){
                    $metrics = $print2->queryFontMetrics($draw, $lines[$i]);
                    $x_pos = $resizeWidth >= $metrics['textWidth'] ? ($resizeWidth - $metrics['textWidth']) : 0;
                }
                $print2->annotateImage($draw, $x_pos, $y_pos + ($i * $line_height * $line_height_ratio), $object->angle, $lines[$i]);
            }

            if ($object->flipX == 1)
                $print2->flopImage(); // x
            if ($object->flipY == 1)
                $print2->flipImage(); // y

//            $print2->trimImage(0);
            $print2->setImagePage(0, 0, 0, 0);

            $print2->resizeImage($resizeWidth, 0, Imagick::FILTER_CATROM, 0.9, false);

            $left = $object->left < 0 ? -1 * abs($this->changeDpi($object->left)) : $this->changeDpi($object->left);
            $top = $object->top < 0 ? -1 * abs($this->changeDpi($object->top)) : $this->changeDpi($object->top);

            $print->compositeImage($print2, Imagick::COMPOSITE_OVER, $left, $top);

            //header("Content-Type: image/png");
            //echo $print2;exit;
        } catch (\Exception $e) {
            echo $e->getMessage();
            LogUtil::info("[addClueTextToLarge]error:".$e->getMessage());
            exit();
        }
    }

    //The greatest common divisor (GCD)
    function gcd($a, $b) {
        return $b ? gcd($b, $a % $b) : $a;
    }

    function changeDpi($px) {
        //return ($px/96)*300;
        return $px;
    }

    function scale($px, $r1, $r2) {
        return $px * $r1 / $r2;
    }
}