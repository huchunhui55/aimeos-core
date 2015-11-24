<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2013
 * @copyright Aimeos (aimeos.org), 2014
 */

$index = 0;
$enc = $this->encoder();
$attributes = $this->get( 'selectionAttributeItems', array() );
$contentUrl = $this->config( 'client/html/common/content/baseurl' );
$prodDeps = json_encode( $this->get( 'selectionProductDependencies', new stdClass() ) );
$attrDeps = json_encode( $this->get( 'selectionAttributeDependencies', new stdClass() ) );


/** client/html/catalog/detail/basket/selection/type/length
 * Layout type for the product length selection
 *
 * This option determines the displayed selection method for the "length"
 * product variant attribute.
 *
 * Note: Up to 2015.03 this option was available as
 * client/html/catalog/detail/basket/selection/length
 *
 * @param string Layout type, e.g. "select" or "radio"
 * @since 2015.04
 * @category Developer
 * @category User
 * @see client/html/catalog/detail/basket/selection/type/width
 */

/** client/html/catalog/detail/basket/selection/type/width
 * Layout type for the product width selection
 *
 * This option determines the displayed selection method for the "width"
 * product variant attribute.
 *
 * Note: Up to 2015.03 this option was available as
 * client/html/catalog/detail/basket/selection/width
 *
 * @param string Layout type, e.g. "select" or "radio"
 * @since 2015.04
 * @category Developer
 * @category User
 * @see client/html/catalog/detail/basket/selection/type/length
 */

?>
<div class="catalog-detail-basket-selection" data-proddeps="<?php echo $enc->attr( $prodDeps ); ?>" data-attrdeps="<?php echo $enc->attr( $attrDeps ); ?>">
<?php foreach( $this->get( 'selectionProducts', array() ) as $prodid => $product ) : ?>
<?php	$prices = $product->getRefItems( 'price', null, 'default' ); ?>
<?php	if( !empty( $prices ) ) : ?>
	<div class="price price-prodid-<?php echo $prodid; ?>">
<?php		echo $this->partial( 'client/html/common/partials/price', 'common/partials/price-default.php', array( 'prices' => $prices ) ); ?>
	</div>
<?php	endif; ?>
<?php endforeach; ?>
	<ul class="selection">
<?php foreach( $this->get( 'selectionAttributeTypeDependencies', array() ) as $code => $attrIds ) : asort( $attrIds ); ?>
<?php	$layout = $this->config( 'client/html/catalog/detail/basket/selection/type/' . $code, 'select' ); ?>
		<li class="select-item <?php echo $enc->attr( $layout ) . ' ' . $enc->attr( $code ); ?>">
			<div class="select-name"><?php echo $enc->html( $this->translate( 'client/html/code', $code ) ); ?></div>
			<div class="select-value">
<?php	if( $layout === 'radio' ) : ?>
				<ul class="select-list" data-index="<?php echo $index++; ?>">
<?php		foreach( $attrIds as $attrId => $position ) : ?>
<?php			if( isset( $attributes[$attrId] ) ) : ?>
					<li class="select-entry">
						<input class="select-option" id="option-<?php echo $enc->attr( $attrId ); ?>" name="<?php echo $enc->attr( $this->formparam( array( 'b_prod', 0, 'attrvarid', $code ) ) ); ?>" type="radio" value="<?php echo $enc->attr( $attrId ); ?>" />
						<label class="select-label" for="option-<?php echo $enc->attr( $attrId ); ?>"><!--
<?php				foreach( $attributes[$attrId]->getListItems( 'media', 'icon' ) as $listItem ) : ?>
<?php					if( ( $item = $listItem->getRefItem() ) !== null ) : ?>
<?php						echo '-->' . $this->partial( 'client/html/common/partials/media', 'common/partials/media-default.php', array( 'item' => $item, 'boxAttributes' => array( 'class' => 'media-item' ) ) ) . '<!--'; ?>
<?php					endif; ?>
<?php				endforeach; ?>
							--><span><?php echo $enc->html( $attributes[$attrId]->getName() ); ?></span><!--
						--></label>
					</li>
<?php			endif; ?>
<?php		endforeach; ?>
				</ul>
<?php	else : ?>
				<select class="select-list" name="<?php echo $enc->attr( $this->formparam( array( 'b_prod', 0, 'attrvarid', $code ) ) ); ?>" data-index="<?php echo $index++; ?>">
					<option class="select-option" value=""><?php echo $enc->attr( $this->translate( 'client/html', 'Please select' ) ); ?></option>
<?php		foreach( $attrIds as $attrId => $position ) : ?>
<?php			if( isset( $attributes[$attrId] ) ) : ?>
					<option class="select-option" value="<?php echo $enc->attr( $attrId ); ?>"><?php echo $enc->html( $attributes[$attrId]->getName() ); ?></option>
<?php			endif; ?>
<?php		endforeach; ?>
				</select>
<?php	endif; ?>
			</div>
		</li>
<?php endforeach; ?>
	</ul>
<?php echo $this->get( 'selectionBody' ); ?>
</div>
