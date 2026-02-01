/**
 * Marginalia Block Editor Components
 *
 * Adds a custom reading status dropdown to the block editor sidebar.
 *
 * @package Marginalia
 */

/* global wp, marginaliaBlockEditor */

(function() {
	'use strict';

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var SelectControl = wp.components.SelectControl;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var createElement = wp.element.createElement;
	var unregisterPlugin = wp.plugins.unregisterPlugin;

	// Remove the default taxonomy panel for reading_status.
	// We need to wait for the editor to be ready.
	wp.domReady(function() {
		// Remove the default reading_status panel from the editor.
		// The default panel ID follows the pattern: taxonomy-panel-{taxonomy}.
		var removeDefaultPanel = wp.data.dispatch('core/edit-post').removeEditorPanel;
		if (removeDefaultPanel) {
			removeDefaultPanel('taxonomy-panel-reading_status');
		}
	});

	/**
	 * Reading Status Panel Component
	 */
	function ReadingStatusPanel() {
		var postType = useSelect(function(select) {
			return select('core/editor').getCurrentPostType();
		}, []);

		var selectedTerms = useSelect(function(select) {
			return select('core/editor').getEditedPostAttribute('reading_status') || [];
		}, []);

		var editPost = useDispatch('core/editor').editPost;

		// Only show for book post type.
		if (postType !== 'book') {
			return null;
		}

		// Get the current selected term ID.
		var currentValue = selectedTerms.length > 0 ? selectedTerms[0] : '';

		// Build options array with empty option first.
		var options = [
			{ label: marginaliaBlockEditor.strings.selectStatus, value: '' }
		];

		// Add reading status options.
		marginaliaBlockEditor.readingStatuses.forEach(function(status) {
			options.push({
				label: status.name,
				value: status.id
			});
		});

		/**
		 * Handle status change.
		 *
		 * @param {string} value Selected term ID.
		 */
		function onStatusChange(value) {
			// Convert to array of integers (or empty array).
			var newTerms = value ? [parseInt(value, 10)] : [];
			editPost({ reading_status: newTerms });
		}

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'marginalia-reading-status',
				title: marginaliaBlockEditor.strings.readingStatus,
				className: 'marginalia-reading-status-panel'
			},
			createElement(
				SelectControl,
				{
					label: marginaliaBlockEditor.strings.statusLabel,
					hideLabelFromVision: true,
					value: currentValue,
					options: options,
					onChange: onStatusChange
				}
			)
		);
	}

	// Register the plugin.
	registerPlugin('marginalia-reading-status', {
		render: ReadingStatusPanel,
		icon: 'book'
	});

})();
