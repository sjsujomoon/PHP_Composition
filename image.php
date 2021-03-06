<?php
/**
 * Image Compositing
 * 
 * @author Jeong Ook Moon
 */

// Constants for comp function
define("OVER", 'OVER'); define("UNDER", 'UNDER');
// define constant variables for lum case
define("W1", .3); define("W2", .6); define("W3", .1);

class Image {
    public $fileName, $image;

    function __construct($fName) {
        $this->fileName = $fName;
        $this->image = $this->loadImg($fName);
    }

    function loadImg($fileName) {
        // Takes care of different img format
        return strpos($fileName, 'png') ? imagecreatefrompng($fileName) : imagecreatefromjpeg($fileName);
    }

    function writeImg($img, $output) {
        // Takes care of different img format
        strpos($output, 'png') ? imagepng($img, $output) : imagejpeg($img, $output);
    }

    function saveImg($fName) {
        $this->fileName = $fName;
        $this->image = $this->loadImg($fName);
    }

    function comp($imgClass, $w_initial, $h_initial, $output, $overUnder) {
        //$overUnder decided whether current class image will be over or under
        $foreground = ($overUnder === UNDER)? $this->loadImg($imgClass->fileName) : $this->loadImg($this->fileName);
        $background = ($overUnder === UNDER)? $this->loadImg($this->fileName) : $this->loadImg($imgClass->fileName);
        
        list( $width_over, $height_over ) = getimagesize( $imgClass->fileName );

        for( $w_index=0; $w_index<$width_over; $w_index++ ) {
            for( $h_index=0; $h_index<$height_over; $h_index++ ) {
                // Get the index of a pixel's color
                $foreground_pixel = imagecolorat( $foreground, $w_index, $h_index );
                $background_pixel = imagecolorat( $background, $w_initial+$w_index, $h_initial+$h_index );
                
                // Make it human readable source: http://php.net/manual/en/function.imagecolorsforindex.php
                $foreground_rgba = imagecolorsforindex($foreground, $foreground_pixel);
                
                // Normalize alpha value
                $a = $foreground_rgba["alpha"]/127.0;

                // C(i,j) = a * A(i.j) + ( 1 – a ) * B(i,j)
                $composite = (1 -$a) * $foreground_pixel + ($a) * $background_pixel;
                imagesetpixel( $background, $w_initial+$w_index, $h_initial+$h_index, $composite);
            }
        }

        $this->writeImg($background, $output);
        $this->saveImg($output);
    }

    function convertImageToGrayScale( $imgOut, $method ){
        // Create an image identifier from a file
        $image = $this->image;
        // Retrieve width & height of the image
        list( $width, $height ) = getimagesize( $this->fileName );
        // Return value to identify if a method runs successfully
        $flag = true;
        ini_set('max_execution_time', 300);
        // Loop through each pixel of the image
        for( $w_index=0; $w_index<$width; $w_index++ ) {
            for( $h_index=0; $h_index<$height; $h_index++ ) {
                // Get the index of a pixel's color
                $color_index = imagecolorat( $image, $w_index, $h_index );
                // Make it human readable source: http://php.net/manual/en/function.imagecolorsforindex.php
                $rgb = imagecolorsforindex($image, $color_index);
                // Assign each r.g.b. index
                $r = $rgb["red"]; $g = $rgb["green"]; $b = $rgb["blue"];
                            
                // get grey pixel value. Use parameter $method to choose a correct conversion method. 
                $grey = $this->$method($r, $g, $b);

                // Get grey color index where r === g === b
                $grey_index = imagecolorallocate($image, $grey, $grey, $grey);
                
                // Make sure if any method returns false
                if($grey_index === false || $rgb === false || imagesetpixel( $image, $w_index, $h_index, $grey_index ) === false) {
                    $flag = false;
                }
            }
        }
        
        // Write converted image to a file
        $this->writeImg($image, $imgOut);
    }
    
    // methods for greyscale conversion
    function avgGrey($r, $g, $b) {
        return ( $r + $g + $b ) / 3;
    }
    function lightGrey($r, $g, $b) {
        return ( max( $r, $g, $b ) + min( $r, $g, $b )) / 2;
    }
    function lumGrey($r, $g, $b) {
        return W1 * $r + W2 * $g + W3 * $b;
    }
}
    $image1 = new Image('over.png');
    $image2 = new Image('under.jpg');
    $image2->comp($image1, 100, 50, 'output.jpg', UNDER);
    $image2->convertImageToGrayScale('greyoutput.jpg', "avgGrey");
?>