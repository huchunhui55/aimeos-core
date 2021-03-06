<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */

$enc = $this->encoder();

?>
<!-- catalog.stage.navigator -->
<?php if( $this->param( 'l_pos' ) !== null ) : ?>
<div class="catalog-stage-navigator">
	<nav>
<?php if( isset( $this->navigationPrev ) ) : ?>
		<a class="prev" href="<?php echo $enc->attr( $this->navigationPrev ); ?>" rel="prev"><?php echo $enc->html( $this->translate( 'client', 'Previous' ), $enc::TRUST ); ?></a>
<?php endif; ?>
<?php if( isset( $this->navigationNext ) ) : ?>
		<a class="next" href="<?php echo $enc->attr( $this->navigationNext ); ?>" rel="next"><?php echo $enc->html( $this->translate( 'client', 'Next' ), $enc::TRUST ); ?></a>
<?php endif; ?>
	</nav>
<?php echo $this->get( 'navigatorBody' ); ?>
</div>
<?php endif; ?>
<!-- catalog.stage.navigator -->
