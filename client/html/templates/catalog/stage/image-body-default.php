<?php

/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2013
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */

$enc = $this->encoder();

?>
<div class="catalog-stage-image">
<?php foreach( $this->get( 'imageItems', array() ) as $media ) : ?>
	<img src="<?php echo $this->content( $media->getUrl() ); ?>" alt="<?php echo $enc->attr( $media->getName() ); ?>" />
<?php endforeach; ?>
<?php echo $this->get( 'imageBody' ); ?>
</div>