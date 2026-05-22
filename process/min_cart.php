<ul class="hm-menu">


                                <li class="hm-minicart">
                                    <div class="hm-minicart-trigger">
                                        <span class="item-icon"></span>
                                        <span class="item-text">Rs 6,700.00
                                            <span class="cart-item-count">2</span>
                                        </span>
                                    </div>
                                    <span></span>
                                    <div class="minicart">
                                        <ul class="minicart-product-list">
                                            <?php
                                            if (!empty($_SESSION['SBCScart'])) {

                                                $total = 0;

                                                $linenumber = 0;
                                                $i = 0;


                                                foreach ($_SESSION['SBCScart'] as $SBCSitem) {
                                                    $i = $i + 1;

                                                    if ($SBCSitem['quantity'] != 0) {


                                                        $pricedecimal = str_replace(",", ".", $SBCSitem['price']);
                                                        $qtydecimal = str_replace(",", ".", $SBCSitem['quantity']);

                                                        $pricedecimal = (float) $pricedecimal;
                                                        $qtydecimal = (float) $qtydecimal;

                                                        $totaldecimal = number_format($pricedecimal * $qtydecimal, 2, '.', ',');

                                                        echo '<li>
                                                                <a href="single-product.html" class="minicart-product-image">
                                                                    <img src="' . $SBCSitem['item_image'] . '" alt="' . $SBCSitem['item'] . '">
                                                                </a>
                                                                <div class="minicart-product-details">
                                                                    <h6><a href="single-product.html">' . $SBCSitem['item'] . '</a></h6>
                                                                    <span>' .currency($totaldecimal) . ' x ' . $SBCSitem['quantity'] . '</span>
                                                                </div>
                                                               <a onclick="cart.remove(' . $SBCSitem['item_id'] . ');"> <button class="close"><i class="fa fa-close"></i></button></a>
                                                            </li>
                                                                                   ';

                                                        $total += $totaldecimal;
                                                    }
                                                    $linenumber++;
                                                }
                                            } else {

                                                echo "<li>No item found.</li>";
                                            }
                                            ?>

                                        </ul>
                                        <p class="minicart-total">SUBTOTAL: <span><?php echo currency($total);?></span></p>
                                        <div class="minicart-button">
                                            <a href="checkout.html" class="li-button li-button-dark li-button-fullwidth li-button-sm">
                                                <span>View Full Cart</span>
                                            </a>
                                            <a href="checkout.html" class="li-button li-button-fullwidth li-button-sm">
                                                <span>Checkout</span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <!-- Header Mini Cart Area End Here -->
                            </ul>