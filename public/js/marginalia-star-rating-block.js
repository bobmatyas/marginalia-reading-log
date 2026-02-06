( function ( blocks, element, serverSideRender ) {
	var el = element.createElement;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'marginalia/star-rating', {
		title: 'Star Rating',
		icon: 'star-filled',
		category: 'widgets',
		description: 'Display the star rating for a book.',
		supports: {
			html: false,
		},
		edit: function () {
			return el( ServerSideRender, {
				block: 'marginalia/star-rating',
			} );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender );
