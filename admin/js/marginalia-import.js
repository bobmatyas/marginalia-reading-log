/**
 * Marginalia CSV Import Wizard.
 *
 * @package Marginalia
 */
( function () {
	'use strict';

	var state = {
		headers: [],
		previewRows: [],
		totalRows: 0,
		mapping: {},
		currentStep: 1,
	};

	/**
	 * Initialize the import wizard.
	 */
	function init() {
		var uploadBtn = document.getElementById( 'marginalia-upload-btn' );
		var confirmBtn = document.getElementById( 'marginalia-confirm-mapping-btn' );
		var startBtn = document.getElementById( 'marginalia-start-import-btn' );

		if ( uploadBtn ) {
			uploadBtn.addEventListener( 'click', handleUpload );
		}
		if ( confirmBtn ) {
			confirmBtn.addEventListener( 'click', handleConfirmMapping );
		}
		if ( startBtn ) {
			startBtn.addEventListener( 'click', handleStartImport );
		}

		// Back buttons.
		var backBtns = document.querySelectorAll( '.marginalia-back-btn' );
		for ( var i = 0; i < backBtns.length; i++ ) {
			backBtns[ i ].addEventListener( 'click', function () {
				goToStep( parseInt( this.getAttribute( 'data-target' ), 10 ) );
			} );
		}
	}

	/**
	 * Navigate to a step.
	 *
	 * @param {number} step Step number.
	 */
	function goToStep( step ) {
		// Hide all panels.
		var panels = document.querySelectorAll( '.marginalia-import-panel' );
		for ( var i = 0; i < panels.length; i++ ) {
			panels[ i ].style.display = 'none';
		}

		// Show target panel.
		var target = document.getElementById( 'marginalia-step-' + step );
		if ( target ) {
			target.style.display = 'block';
		}

		// Update step indicators.
		var steps = document.querySelectorAll( '.marginalia-import-step' );
		for ( var j = 0; j < steps.length; j++ ) {
			var stepNum = parseInt( steps[ j ].getAttribute( 'data-step' ), 10 );
			steps[ j ].classList.remove( 'active', 'completed' );
			if ( stepNum === step ) {
				steps[ j ].classList.add( 'active' );
			} else if ( stepNum < step ) {
				steps[ j ].classList.add( 'completed' );
			}
		}

		state.currentStep = step;
	}

	/**
	 * Handle CSV upload.
	 */
	function handleUpload() {
		var fileInput = document.getElementById( 'marginalia-csv-file' );
		var statusEl = document.getElementById( 'marginalia-upload-status' );

		if ( ! fileInput || ! fileInput.files.length ) {
			statusEl.textContent = marginaliaImport.strings.no_file;
			statusEl.className = 'marginalia-import-status error';
			return;
		}

		var formData = new FormData();
		formData.append( 'action', 'marginalia_upload_csv' );
		formData.append( 'nonce', marginaliaImport.nonce );
		formData.append( 'csv_file', fileInput.files[0] );

		statusEl.textContent = marginaliaImport.strings.uploading;
		statusEl.className = 'marginalia-import-status';

		var uploadBtn = document.getElementById( 'marginalia-upload-btn' );
		uploadBtn.disabled = true;

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', marginaliaImport.ajax_url );
		xhr.onload = function () {
			uploadBtn.disabled = false;
			var response;
			try {
				response = JSON.parse( xhr.responseText );
			} catch ( e ) {
				statusEl.textContent = marginaliaImport.strings.upload_error;
				statusEl.className = 'marginalia-import-status error';
				return;
			}

			if ( response.success ) {
				state.headers = response.data.headers;
				state.previewRows = response.data.preview_rows;
				state.totalRows = response.data.total_rows;
				buildMappingTable();
				goToStep( 2 );
			} else {
				statusEl.textContent = response.data.message || marginaliaImport.strings.upload_error;
				statusEl.className = 'marginalia-import-status error';
			}
		};
		xhr.onerror = function () {
			uploadBtn.disabled = false;
			statusEl.textContent = marginaliaImport.strings.upload_error;
			statusEl.className = 'marginalia-import-status error';
		};
		xhr.send( formData );
	}

	/**
	 * Build the field mapping table.
	 */
	function buildMappingTable() {
		var tbody = document.querySelector( '#marginalia-mapping-table tbody' );
		tbody.innerHTML = '';

		var autoMap = marginaliaImport.auto_detect_map;
		var targetFields = marginaliaImport.target_fields;

		for ( var i = 0; i < state.headers.length; i++ ) {
			var header = state.headers[ i ];
			var previewValue = state.previewRows.length > 0 && state.previewRows[0][ i ]
				? state.previewRows[0][ i ]
				: '';

			// Auto-detect mapping.
			var headerLower = header.toLowerCase().trim();
			var autoDetected = autoMap[ headerLower ] || '';

			var row = document.createElement( 'tr' );

			// Column name cell.
			var nameCell = document.createElement( 'td' );
			nameCell.textContent = header;
			nameCell.className = 'marginalia-col-name';
			row.appendChild( nameCell );

			// Preview value cell.
			var previewCell = document.createElement( 'td' );
			previewCell.textContent = previewValue.length > 80
				? previewValue.substring( 0, 80 ) + '...'
				: previewValue;
			previewCell.className = 'marginalia-col-preview';
			row.appendChild( previewCell );

			// Mapping dropdown cell.
			var mapCell = document.createElement( 'td' );
			var select = document.createElement( 'select' );
			select.className = 'marginalia-field-select';
			select.setAttribute( 'data-col-index', i );

			for ( var key in targetFields ) {
				if ( targetFields.hasOwnProperty( key ) ) {
					var option = document.createElement( 'option' );
					option.value = key;
					option.textContent = targetFields[ key ];
					if ( key === autoDetected ) {
						option.selected = true;
					}
					select.appendChild( option );
				}
			}

			mapCell.appendChild( select );
			row.appendChild( mapCell );
			tbody.appendChild( row );
		}
	}

	/**
	 * Handle confirm mapping step.
	 */
	function handleConfirmMapping() {
		// Gather mapping.
		var selects = document.querySelectorAll( '.marginalia-field-select' );
		var mapping = {};
		var hasTitleMapping = false;

		for ( var i = 0; i < selects.length; i++ ) {
			var colIndex = selects[ i ].getAttribute( 'data-col-index' );
			var value = selects[ i ].value;
			mapping[ colIndex ] = value;
			if ( value === 'post_title' ) {
				hasTitleMapping = true;
			}
		}

		if ( ! hasTitleMapping ) {
			alert( marginaliaImport.strings.no_title );
			return;
		}

		state.mapping = mapping;

		// Build confirmation summary.
		buildConfirmSummary();
		goToStep( 3 );
	}

	/**
	 * Build the confirmation summary.
	 */
	function buildConfirmSummary() {
		var summaryEl = document.getElementById( 'marginalia-confirm-summary' );
		var targetFields = marginaliaImport.target_fields;

		var html = '<table class="widefat"><thead><tr>';
		html += '<th>' + 'CSV Column' + '</th>';
		html += '<th>' + 'Mapped To' + '</th>';
		html += '</tr></thead><tbody>';

		for ( var colIndex in state.mapping ) {
			if ( state.mapping.hasOwnProperty( colIndex ) ) {
				var field = state.mapping[ colIndex ];
				if ( ! field ) {
					continue;
				}
				var colName = state.headers[ parseInt( colIndex, 10 ) ] || '';
				var fieldLabel = targetFields[ field ] || field;
				html += '<tr><td>' + escHtml( colName ) + '</td><td>' + escHtml( fieldLabel ) + '</td></tr>';
			}
		}

		html += '</tbody></table>';
		html += '<p><strong>' + state.totalRows + '</strong> rows will be imported.</p>';

		var skipDup = document.getElementById( 'marginalia-skip-duplicates' );
		var fetchCovers = document.getElementById( 'marginalia-fetch-covers' );
		var postStatus = document.getElementById( 'marginalia-post-status' );

		html += '<ul>';
		if ( skipDup && skipDup.checked ) {
			html += '<li>Duplicates will be skipped</li>';
		}
		if ( fetchCovers && fetchCovers.checked ) {
			html += '<li>Cover images will be fetched from OpenLibrary</li>';
		}
		html += '<li>Post status: ' + escHtml( postStatus.options[ postStatus.selectedIndex ].text ) + '</li>';
		html += '</ul>';

		summaryEl.innerHTML = html;
	}

	/**
	 * Handle start import.
	 */
	function handleStartImport() {
		goToStep( 4 );

		var logEl = document.getElementById( 'marginalia-import-log' );
		logEl.innerHTML = '';

		var counts = { imported: 0, skipped: 0, errors: 0 };
		var skipDup = document.getElementById( 'marginalia-skip-duplicates' );
		var fetchCovers = document.getElementById( 'marginalia-fetch-covers' );
		var postStatus = document.getElementById( 'marginalia-post-status' );

		processBatch( 0, counts, skipDup.checked, fetchCovers.checked, postStatus.value );
	}

	/**
	 * Process a batch of imports.
	 *
	 * @param {number}  offset       Current offset.
	 * @param {Object}  counts       Running counts.
	 * @param {boolean} skipDup      Skip duplicates flag.
	 * @param {boolean} fetchCovers  Fetch covers flag.
	 * @param {string}  postStatus   Post status.
	 */
	function processBatch( offset, counts, skipDup, fetchCovers, postStatus ) {
		var formData = new FormData();
		formData.append( 'action', 'marginalia_import_batch' );
		formData.append( 'nonce', marginaliaImport.nonce );
		formData.append( 'offset', offset );
		formData.append( 'skip_duplicates', skipDup ? 'true' : 'false' );
		formData.append( 'fetch_covers', fetchCovers ? 'true' : 'false' );
		formData.append( 'post_status', postStatus );

		// Send mapping.
		for ( var key in state.mapping ) {
			if ( state.mapping.hasOwnProperty( key ) ) {
				formData.append( 'mapping[' + key + ']', state.mapping[ key ] );
			}
		}

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', marginaliaImport.ajax_url );
		xhr.onload = function () {
			var response;
			try {
				response = JSON.parse( xhr.responseText );
			} catch ( e ) {
				appendLog( 'Error: Invalid response from server', 'error' );
				showImportDone( counts );
				return;
			}

			if ( ! response.success ) {
				if ( response.data && response.data.message === 'transient_expired' ) {
					appendLog( marginaliaImport.strings.transient_expired, 'error' );
				} else {
					appendLog( response.data.message || 'Import error', 'error' );
				}
				showImportDone( counts );
				return;
			}

			var data = response.data;
			var results = data.results;

			for ( var i = 0; i < results.length; i++ ) {
				var result = results[ i ];
				if ( result.status === 'imported' ) {
					counts.imported++;
				} else if ( result.status === 'skipped' ) {
					counts.skipped++;
				} else {
					counts.errors++;
				}

				var logText = marginaliaImport.strings.row + ' ' + result.row + ': ';
				if ( result.title ) {
					logText += '"' + result.title + '" — ';
				}
				logText += result.message;
				appendLog( logText, result.status );
			}

			// Update progress.
			var progress = Math.round( ( data.next_offset / data.total_rows ) * 100 );
			updateProgress( progress, data.next_offset, data.total_rows );

			if ( data.done ) {
				showImportDone( counts );
			} else {
				processBatch( data.next_offset, counts, skipDup, fetchCovers, postStatus );
			}
		};
		xhr.onerror = function () {
			appendLog( 'Network error during import', 'error' );
			showImportDone( counts );
		};
		xhr.send( formData );
	}

	/**
	 * Append a message to the import log.
	 *
	 * @param {string} message Log message.
	 * @param {string} status  Status class.
	 */
	function appendLog( message, status ) {
		var logEl = document.getElementById( 'marginalia-import-log' );
		var entry = document.createElement( 'div' );
		entry.className = 'marginalia-log-entry marginalia-log-' + status;
		entry.textContent = message;
		logEl.appendChild( entry );
		logEl.scrollTop = logEl.scrollHeight;
	}

	/**
	 * Update progress bar.
	 *
	 * @param {number} percent  Progress percentage.
	 * @param {number} current  Current row.
	 * @param {number} total    Total rows.
	 */
	function updateProgress( percent, current, total ) {
		var fill = document.getElementById( 'marginalia-progress-fill' );
		var text = document.getElementById( 'marginalia-progress-text' );
		fill.style.width = percent + '%';
		text.textContent = current + ' / ' + total + ' (' + percent + '%)';
	}

	/**
	 * Show import completion summary.
	 *
	 * @param {Object} counts Import counts.
	 */
	function showImportDone( counts ) {
		updateProgress( 100, state.totalRows, state.totalRows );

		var summaryEl = document.getElementById( 'marginalia-import-summary' );
		var html = '<h3>' + marginaliaImport.strings.import_complete + '</h3>';
		html += '<ul>';
		html += '<li><strong>' + counts.imported + '</strong> ' + marginaliaImport.strings.imported + '</li>';
		html += '<li><strong>' + counts.skipped + '</strong> ' + marginaliaImport.strings.skipped + '</li>';
		html += '<li><strong>' + counts.errors + '</strong> ' + marginaliaImport.strings.error + '</li>';
		html += '</ul>';
		summaryEl.innerHTML = html;
		summaryEl.style.display = 'block';

		document.getElementById( 'marginalia-import-done-actions' ).style.display = 'block';
	}

	/**
	 * Escape HTML entities.
	 *
	 * @param {string} str Input string.
	 * @return {string} Escaped string.
	 */
	function escHtml( str ) {
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	// Initialize on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
