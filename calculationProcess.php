<?php
session_start();


$total = 0;
$linenumber = 0;
$i = 0;
if (!empty($_SESSION['SBCScart'])) {

    $total = 0;

    $linenumber = 0;
    $i = 0;


    foreach ($_SESSION['SBCScart'] as $SBCSitem) {
        $i = $i + 1;

        if ($SBCSitem['quantity'] != 0) {


            $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
            $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);
            $get_item_discount = str_replace(",", ".", $SBCSitem['item_discount']);
            $getItemWeight = $SBCSitem['item_weight'];
            $pricedecimal = (float) $pricedecimal;
            $qtydecimal = (float) $qtydecimal;
            $get_item_discount = $get_item_discount;
            $discount_value = ($SBCSitem['price'] * ($SBCSitem['item_discount'] * $SBCSitem['quantity'])) / 100;
            $totaldecimal = $pricedecimal * $qtydecimal;
            $totaldecimal = $totaldecimal - $discount_value;

            $total += $totaldecimal;
            $linenumber++;
        }
    }
}

echo $total;
