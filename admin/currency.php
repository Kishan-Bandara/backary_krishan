<?php

{

$db = new Database();

$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
echo $currency = $getcurrency['currency'];

}


?>



