<?php
/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/blockonomics.php');

if (!$cookie->isLogged(true))
    Tools::redirect('authentication.php?back=order.php');

$blockonomics = new Blockonomics();
echo $blockonomics->showConfirmationPage($cart);

include_once(dirname(__FILE__).'/../../footer.php');

?>
