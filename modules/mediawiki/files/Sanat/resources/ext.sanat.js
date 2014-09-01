/**
 * @author Niklas Laxström
 * @license MIT
 */

(function ( $, mw ) {
	'use strict';

	if ( !mw.user.isAnon() ) {
		var $tools = $( '.sanat-inlinetools' );

		$tools.each( function () {
			var target, $tool = $( this );

			target = $( this ).data( 'sanat-entry' );
			if ( !target ) {
					return;
			}

			$tool.append(
				$( '<form>' )
					.attr( 'method', 'post' )
					.attr( 'action', mw.util.getUrl( target, { action: 'delete' } ) )
					.append(
						$( '<button type="submit"></button>' )
							.text( mw.msg( 'sanat-inlinetools-delete-example' ) )
					 )
			);
		} );

		$tools.show();

	}
}( jQuery, mediaWiki ) );
