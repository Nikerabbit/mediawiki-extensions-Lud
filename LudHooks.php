<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

class LudHooks {
	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModuleStyles( 'ext.lud.styles' );
	}
}
