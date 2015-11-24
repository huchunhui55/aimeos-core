<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */

$enc = $this->encoder();

$detailTarget = $this->config( 'client/html/catalog/detail/url/target' );
$detailController = $this->config( 'client/html/catalog/detail/url/controller', 'catalog' );
$detailAction = $this->config( 'client/html/catalog/detail/url/action', 'detail' );
$detailConfig = $this->config( 'client/html/catalog/detail/url/config', array() );

/// Price format with price value (%1$s) and currency (%2$s)
$priceFormat = $this->translate( 'client/html', '%1$s %2$s' );
/// Percent format with value (%1$s) and % sign
$percentFormat = $this->translate( 'client/html', '%1$s%%' );

$position = $this->get( 'itemPosition', 0 );


/** client/html/common/partials/price
 * Relative path to the price partial template file
 *
 * Partials are templates which are reused in other templates and generate
 * reoccuring blocks filled with data from the assigned values. The price
 * partial creates an HTML block for a list of price items.
 *
 * The partial template files are usually stored in the templates/partials/ folder
 * of the core or the extensions. The configured path to the partial file must
 * be relative to the templates/ folder, e.g. "partials/price-default.php".
 *
 * @param string Relative path to the template file
 * @since 2015.04
 * @category Developer
 */

?>
<div class="catalog-list-items">
	<ul class="list-items"><!--
<?php foreach( $this->get( 'listProductItems', array() ) as $id => $productItem ) : $firstImage = true; ?>
<?php	$params = array( 'd_name' => $productItem->getName( 'url' ), 'd_prodid' => $id, 'l_pos' => $position++ ); ?>
<?php	$conf = $productItem->getConfig(); $css = ( isset( $conf['css-class'] ) ? $conf['css-class'] : '' ); ?>
	--><li class="product <?php echo $enc->attr( $css ); ?>">
		<a href="<?php echo $enc->attr( $this->url( $detailTarget, $detailController, $detailAction, $params, array(), $detailConfig ) ); ?>">
			<div class="media-list">
<?php	foreach( $productItem->getRefItems( 'media', 'default', 'default' ) as $mediaItem ) : ?>
<?php		$mediaUrl = $this->content( $mediaItem->getPreview() ); ?>
<?php		if( $firstImage === true ) : $firstImage = false; ?>
				<noscript><div class="media-item" style="background-image: url('<?php echo $mediaUrl; ?>')"></div></noscript>
				<div class="media-item lazy-image" data-src="<?php echo $mediaUrl; ?>"></div>
<?php		else : ?>
				<div class="media-item" data-src="<?php echo $mediaUrl; ?>"></div>
<?php		endif; ?>
<?php	endforeach; ?>
			</div>
			<div class="text-list">
				<h2><?php echo $enc->html( $productItem->getName(), $enc::TRUST ); ?></h2>
<?php	foreach( $productItem->getRefItems( 'text', 'short', 'default' ) as $textItem ) : ?>
				<div class="text-item">
<?php		echo $enc->html( $textItem->getContent(), $enc::TRUST ); ?><br/>
				</div>
<?php	endforeach; ?>
			</div>
			<div class="stock" data-prodid="<?php echo $id; ?>"></div>
			<div class="price-list">
<?php	echo $this->partial( 'client/html/common/partials/price', 'common/partials/price-default.php', array( 'prices' => $productItem->getRefItems( 'price', null, 'default' ) ) ); ?>
			</div>
<?php	echo $this->get( 'itemsBody' ); ?>
		</a>
	</li><!--
<?php endforeach; ?>
--></ul>
</div>
