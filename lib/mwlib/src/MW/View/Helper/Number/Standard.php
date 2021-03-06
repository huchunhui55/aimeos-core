<?php

/**
 * @copyright Metaways Infosystems GmbH, 2012
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View\Helper\Number;


/**
 * View helper class for formatting numbers.
 *
 * @package MW
 * @subpackage View
 */
class Standard
	extends \Aimeos\MW\View\Helper\Base
	implements \Aimeos\MW\View\Helper\Number\Iface
{
	private $dsep;
	private $tsep;


	/**
	 * Initializes the Number view helper.
	 *
	 * @param \Aimeos\MW\View\Iface $view View instance with registered view helpers
	 * @param string $decimalSeparator Character for the decimal point
	 * @param string $thousandsSeperator Character separating groups of thousands
	 */
	public function __construct( $view, $decimalSeparator = '.', $thousandsSeperator = '' )
	{
		parent::__construct( $view );

		$this->dsep = $decimalSeparator;
		$this->tsep = $thousandsSeperator;
	}


	/**
	 * Returns the formatted number.
	 *
	 * @param int|float|decimal $number Number to format
	 * @param integer $decimals Number of decimals behind the decimal point
	 * @return string Formatted number
	 */
	public function transform( $number, $decimals = 2 )
	{
		return number_format( $number, $decimals, $this->dsep, $this->tsep );
	}
}