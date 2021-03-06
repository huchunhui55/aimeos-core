<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */

$enc = $this->encoder();


$detailTarget = $this->config( 'client/html/catalog/detail/url/target' );
$detailController = $this->config( 'client/html/catalog/detail/url/controller', 'catalog' );
$detailAction = $this->config( 'client/html/catalog/detail/url/action', 'detail' );
$detailConfig = $this->config( 'client/html/catalog/detail/url/config', array( 'absoluteUri' => 1 ) );


/// Price quantity format with quantity (%1$s)
$quantityFormat = $this->translate( 'client', 'from %1$s' );

/// Price format with price value (%1$s) and currency (%2$s)
$priceFormat = $this->translate( 'client', '%1$s %2$s' );

/// Price shipping format with shipping / payment cost value (%1$s) and currency (%2$s)
$costFormat = $this->translate( 'client', '+ %1$s %2$s/item' );

/// Rebate format with rebate value (%1$s) and currency (%2$s)
$rebateFormat = $this->translate( 'client', '%1$s %2$s off' );

/// Rebate percent format with rebate percent value (%1$s)
$rebatePercentFormat = '(' . $this->translate( 'client', '-%1$s%%' ) . ')';

/// Tax rate format with tax rate in percent (%1$s)
$vatFormat = $this->translate( 'client', 'Incl. %1$s%% VAT' );


?>



<?php echo strip_tags( $this->translate( 'client', 'Watched products' ) ); ?>:
<?php foreach( $this->extProducts as $entry ) : $product = $entry['item']; ?>

<?php	echo strip_tags( $product->getName() ); ?>


<?php	$price = $entry['price']; $priceCurrency = $this->translate( 'client/currency', $price->getCurrencyId() ); ?>
<?php	printf( $priceFormat, $this->number( $price->getValue() ), $priceCurrency ); ?> <?php ( $price->getRebate() > '0.00' ? printf( $rebatePercentFormat, $this->number( round( $price->getRebate() * 100 / ( $price->getValue() + $price->getRebate() ) ), 0 ) ) : '' ); ?>
<?php	if( $price->getCosts() > 0 ) { echo ' ' . strip_tags( sprintf( $costFormat, $this->number( $price->getCosts() ), $priceCurrency ) ); } ?>
<?php	if( $price->getTaxrate() > 0 ) { echo ', ' . strip_tags( sprintf( $vatFormat, $this->number( $price->getTaxrate() ) ) ); } ?>

<?php	$params = array( 'd_prodid' => $product->getId(), 'd_name' => $product->getName( 'url' ) ); ?>
<?php	echo $this->url( $detailTarget, $detailController, $detailAction, $params, array(), $detailConfig ); ?>

<?php endforeach; ?>
<?php echo $this->get( 'detailBody' ); ?>
