(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var XSRF = (document.cookie.match('(^|; )_ce_xsrf=([^;]*)')||0)[2];

	$(window).on('load', function() {
		list();
	});

	$(window).on('hashchange', function() {
		list();
	});

	// $(document).ajaxComplete(function() {
	// });

	$(document).ready( function() {

        // Remove codestar <form> parent element
        // https://bobbyhadz.com/blog/javascript-remove-parent-element

        const child = $('.csf-header')[0];

        child.parentElement.replaceWith(...child.parentElement.childNodes);

        // Remove codestar hidden inputs
        $('.csf-section-id').remove();
        $('#csf_options_noncedatabase-admin').remove();
        $('input[name="_wp_http_referer"]').remove();

        // Add review, feedback and donate buttons

        var addReview = '<a href="https://wordpress.org/plugins/code-explorer/#reviews" target="_blank" class="header-action"><span>&starf;</span> Review</a>';
        var giveFeedback = '<a href="https://wordpress.org/support/plugin/code-explorer/" target="_blank" class="header-action">&#10010; Feedback</a>';
        var donate = '<a href="https://paypal.me/qriouslad" target="_blank" class="header-action">&#10084; Donate</a>';

        $(donate).prependTo('.ce .csf-header-right');
        $(giveFeedback).prependTo('.ce .csf-header-right');
        $(addReview).prependTo('.ce .csf-header-right');

		var urlParams = new URLSearchParams(window.location.search);

		// If not on a 'do=view' page, i.e. file viewer.
		if ( ! urlParams.has('do') ) {

		// Delete file / folder

			$('#table').on('click','.delete',function(data) {
				$.post("",{
					'do': 'delete',
					'file': $(this).attr('data-file'),
					'nonce': $(this).attr('data-nonce'),
					'xsrf': XSRF
				},
				function(data){

					if ( data.success ) {
						list();
					} else {
						alert( 'Oops, something went wrong...' );
					}
				},
				'json');
				return false;
			});

			// Create file or folder

			$('.newfile-button').on('click', function(e) {

				e.preventDefault();

				$('.action-inputs').css("position","initial");
				$('.action-inputs').css("left","unset");
				$('.action-newfile').css("visibility","visible");
				$('.action-newfile').css("height","auto");

				$('.action-newfolder').css("visibility","hidden");
				$('.action-newfolder').css("height","0px");

			});

			$('.newfolder-button').on('click', function(e) {

				e.preventDefault();

				$('.action-inputs').css("position","initial");
				$('.action-inputs').css("left","unset");
				$('.action-newfolder').css("visibility","visible");
				$('.action-newfolder').css("height","auto");

				$('.action-newfile').css("visibility","hidden");
				$('.action-newfile').css("height","0px");

			});

			$('.cancel-action').on('click', function(e) {

				e.preventDefault();

				$('.action-inputs').css("position","absolute");
				$('.action-inputs').css("left","-1000vw");

				$('.action-newfile').css("visibility","hidden");
				$('.action-newfile').css("height","0px");
				$('.action-newfolder').css("visibility","hidden");
				$('.action-newfolder').css("height","0px");

			});

			// Create file

			$('#create-file').on('click', function(e) {
				var fileName = document.getElementById("new-filename").value;

				if (fileName === "") {
					alert("Please enter the file name first.");
					e.preventDefault();
				} else {
					createFile(fileName);
					e.preventDefault();
				}
			});

			var filenameInput = document.getElementById("new-filename");

			// Execute a function when the user releases a key on the keyboard
			filenameInput.addEventListener("keyup", function(e) {

				// Number 13 is the "Enter" key on the keyboard
				if (e.keyCode == 13 ) {
					e.preventDefault();
					document.getElementById("create-file").click();
				}

			});

			// Create folder

			$('#create-folder').on('click', function(e) {
				var folderName = document.getElementById("new-foldername").value;

				if (folderName === "") {
					alert("Please enter the folder name first.");
					e.preventDefault();
				} else {
					createFolder(folderName);
					e.preventDefault();
				}
			});

			var foldernameInput = document.getElementById("new-foldername");

			// Execute a function when the user releases a key on the keyboard
			foldernameInput.addEventListener("keyup", function(e) {

				// Number 13 is the "Enter" key on the keyboard
				if (e.keyCode == 13 ) {
					e.preventDefault();
					document.getElementById("create-folder").click();
				}

			});

		}

	});

	function list() {

		var hashval = window.location.hash.substr(1);

		$.get('?page=code-explorer&do=list&file='+ hashval,function(data) {

			$('#list').empty();
			$('#breadcrumb').empty().html(renderBreadcrumbs(hashval,data.abspath_hash));

			if (data.success) {
				if (data.show_action_buttons) {
					$('.action-buttons').css("visibility","visible");
				} else {
					$('.action-buttons').css("visibility","hidden");
				}
				$.each(data.results,function(k,v){
					$('#list').append(renderFileRow(v));
				});
				!data.results.length && $('#list').append('<tr><td class="empty" colspan=5>This folder is empty</td></tr>')
				data.is_writable ? $('.fmbody .csf-fieldset').removeClass('no_write') : $('.fmbody .csf-fieldset').addClass('no_write');
			} else {
				// console.warn(data.error.msg);
				$('#top').hide();
				$('#table').css('margin-top','25px');
				$('#list').append('<tr><td class="empty" colspan=5>' + data.error_message + '</td></tr>');
			}

			if ( data.editing_enabled === false ) {
				$('.edit').hide();
			}

		},'json');
	}

	function renderFileRow(data) {

		var $filename_view_link = $('<a class="name" />')
			.attr('href', data.is_dir ? '#' + encodeURIComponent(data.path) : ( data.is_viewable ? '?page=code-explorer&do=view&file='+ encodeURIComponent(data.path) : '../' + data.relpath ) )
			.text(data.name);

		var $view_link = '<a href="?page=code-explorer&amp;do=view&amp;file=' + encodeURIComponent(data.path) + '" class="view">View</a>';

		if (data.is_editable) {
			if ( data.editable_type == 'theme' ) {
				var $editor_path = '/wp-admin/theme-editor.php?file=';
				var $selector_path = '&theme=';
			} else if ( data.editable_type == 'plugin' ) {
				var $editor_path = '/wp-admin/plugin-editor.php?file=';
				var $selector_path = '&plugin=';
			} else {}
		}

		var $edit_link = '<a href="' + $editor_path + encodeURIComponent(data.edit_path) + $selector_path + encodeURIComponent(data.edit_selector) + '" class="edit" target="_blank">Edit</a>';

		var $download_link = '<a href="?page=code-explorer&amp;do=download&amp;file=' + encodeURIComponent(data.path) + '" class="download">Download</a>';

		var $delete_link = '<a href="#" data-file="' + data.path + '" data-nonce="' + data.deletion_nonce + '" class="delete">Delete</a>';

		var $action_links = '';

		if ( data.is_viewable ) {
			$action_links += $view_link;
		}

		if ( data.is_editable ) {
			$action_links += $edit_link;
		}

		if ( data.is_downloadable ) {
			$action_links += $download_link;
		}

		if ( data.is_deletable ) {
			$action_links += $delete_link;			
		}

		var perms = [];

		if(data.is_readable) perms.push('Read');
		if(data.is_writable) perms.push('Write');
		if(data.is_executable) perms.push('Exec');

		var $html = $('<tr />')
			.addClass(data.is_dir ? 'is_dir' : '')
			.append( $('<td class="first" />').append($filename_view_link) )
			.append( $('<td/>').append( $action_links ).addClass('td-actions') )
			.append( $('<td/>').html($('<span class="size" />').text(formatFileSize(data.size))) )
			.append( $('<td/>').text(formatTimestamp(data.mtime)) )
			.append( $('<td/>').text(perms.join('+')) )
		return $html;

	}

	function renderBreadcrumbs(path,abspath) {

		var base = "%2F",
			relPath = path.replace( abspath, "" ),
			pathArr = relPath.split('%2F'),
			currentLocation = pathArr.slice(-1)[0];

		var $html = $('<div/>').addClass('breadcrumb-links').append( $('<a href=#>Home</a></div>') );

		$.each(pathArr,function(k,v){
			if(v) {
				var v_as_text = decodeURIComponent(v);

				$html.append( $('<span/>').text(' ▸ ') )
					.append( $('<a/>').attr('href','#'+abspath+base+v).text(v_as_text) );

				base += v + '%2F';
			}
		});

		return $html;

	}

	function formatTimestamp(unix_timestamp) {
		var m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		var d = new Date(unix_timestamp*1000);
		return [m[d.getMonth()],' ',d.getDate(),', ',d.getFullYear()," ",
			(d.getHours() % 12 || 12),":",(d.getMinutes() < 10 ? '0' : '')+d.getMinutes(),
			" ",d.getHours() >= 12 ? 'PM' : 'AM'].join('');
	}

	function formatFileSize(bytes) {
		var s = ['bytes', 'KB','MB','GB','TB','PB','EB'];
		for(var pos = 0;bytes >= 1000; pos++,bytes /= 1024);
		var d = Math.round(bytes*10);
		return pos ? [parseInt(d/10),".",d%10," ",s[pos]].join('') : bytes + ' bytes';
	}

	function createFile(fileName) {

		var hashval = window.location.hash.substr(1);
		var createFileNonce = document.getElementById("create-file-nonce").value;

		$.get('?page=simple-file-manager&do=createfile&_cfilenonce=' + createFileNonce + '&file='+ hashval + '%2F' + fileName,function(data) {

			if(data.success) {
				$('.cancel-newfile').click();
				$('#new-filename').val('');
				list();
			} else {
				alert(data.message);
			}

		},'json');
	}

	function createFolder(folderName) {

		var hashval = window.location.hash.substr(1);
		var createFolderNonce = document.getElementById("create-folder-nonce").value;

		$.get('?page=simple-file-manager&do=createfolder&_cfoldernonce=' + createFolderNonce + '&file='+ hashval + '%2F' + folderName,function(data) {

			if(data.success) {
				$('.cancel-newfolder').click();
				$('#new-foldername').val('');
				list();
			} else {
				alert(data.message);
			}

		},'json');
	}

})( jQuery );
