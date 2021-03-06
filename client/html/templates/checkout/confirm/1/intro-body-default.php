<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */

$enc = $this->encoder();

?>
<div class="checkout-confirm-intro">
	<p class="note"><?php echo nl2br( $enc->html( $this->translate( 'client', 'The order was canceled.
Do you wish to retry your order?' ), $enc::TRUST ) ); ?></p>
<?php echo $this->get( 'introBody' ); ?>
</div>
