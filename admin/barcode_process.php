<?php
// Including all barcode classes
require_once('assets/barcode/class/BCGFontFile.php');
require_once('assets/barcode/class/BCGColor.php');
require_once('assets/barcode/class/BCGDrawing.php');
// Including the barcode technology
require_once('assets/barcode/class/BCGcode39.barcode.php');

//product id eka gannawa
$pid = $_GET['pid'];


                            //  Font load karagannawa 
                            $font = new BCGFontFile('assets/barcode/font/Arial.ttf', 18);

                            // user input eka varialbe ekata assign karagannawa
                            $text = isset($_GET['text']) ? $_GET['text'] : $pid;

                            // The arguments are R, G, B for color.
                            $color_black = new BCGColor(0, 0, 0);
                            $color_white = new BCGColor(255, 255, 255);

                            $drawException = null;
                            try {
                                $code = new BCGcode39();
                                $code->setScale(2); // Resolution eka scale karagannawa
                                $code->setThickness(30); // Thickness eka scale karagannawa
                                $code->setForegroundColor($color_black); // Font color eka 
                                $code->setBackgroundColor($color_white); //background color eka 
                                $code->setFont($font);
                                $code->parse($text); 
                            } catch(Exception $exception) {
                                $drawException = $exception;
                            }

                          
                            $drawing = new BCGDrawing('', $color_white);
                            if($drawException) {
                                $drawing->drawException($drawException);
                            } else {
                                $drawing->setBarcode($code);
                                $drawing->draw();
                            }
                             
                            
                            header('Content-Type: image/png');
                            header('Content-Disposition: inline; filename="barcode.png"');

                            //display karagannawa 
                            $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);

?>



