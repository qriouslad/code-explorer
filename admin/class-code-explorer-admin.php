<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://bowo.io
 * @since      1.0.0
 *
 * @package    Code_Explorer
 * @subpackage Code_Explorer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Code_Explorer
 * @subpackage Code_Explorer/admin
 * @author     Bowo <hello@bowo.io>
 */
class Code_Explorer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Code_Explorer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Code_Explorer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/code-explorer-admin.css', array(), $this->version, 'all' );

		wp_enqueue_style( $this->plugin_name . '-prism', plugin_dir_url( __FILE__ ) . 'css/prism.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Code_Explorer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Code_Explorer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name . '-prism', plugin_dir_url( __FILE__ ) . 'js/prism.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/code-explorer-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * The code explorer
	 *
	 * @link https://github.com/jcampbell1/simple-file-manager
	 * @since 1.0.0
	 */
	public function ce_index() {

		// Limit file manager access only to site admins
		if ( current_user_can( 'manage_options' ) ) {

			//Disable error report for undefined superglobals
			error_reporting( error_reporting() & ~E_NOTICE );

			// Security options
			$allow_show_folders = true; // Set to false to hide all subdirectories

			// Deletion options
			$allow_delete = true; // Allow deletion of folders and files

			// Matching files not allowed to be uploaded. Must be an array.
			$disallowed_patterns = []; // e.g. ['*.php']  

			// Matching files hidden in directory index
			$hidden_patterns = []; // e.g. ['*.php','.*']

			// must be in UTF-8 or `basename` doesn't work
			setlocale(LC_ALL,'en_US.UTF-8');

			// Set cookie and get/set user ID for use in nonces

			if( !$_COOKIE['_ce_xsrf'] ) {
				setcookie( '_ce_xsrf', bin2hex( openssl_random_pseudo_bytes( 16 ) ) );
			}

			if ( is_user_logged_in() ) {

				$uid = (string) get_current_user_id();

			} else {

				$uid = '007';

			}

			// Set WordPress root path

			$abspath = rtrim( ABSPATH, '/' ); // remove trailing slash
			$abspath_hash = urlencode( $abspath );

			// Set the directory/file path

			if ( isset( $_REQUEST['file'] ) ) {

				$file_path = sanitize_url( $_REQUEST['file'] );

				$relpath = str_replace( ABSPATH, '', $file_path );

				if ( !empty( $file_path ) ) {

					$file = $file_path;

				} else {

					$file = $abspath;

				}

			} else {

				$file = $abspath;

			}

			if ( ( isset( $_GET['do'] ) ) && ( $_GET['do'] == 'list' ) ) {

				// Create deletion nonce for javascript

				$deletion_nonce = wp_create_nonce( 'deletion-nonce_' . $_COOKIE['_ce_xsrf'] . $uid );

				// Return list of directories and files data for frontend AJAX request

				if ( is_dir( $file ) ) {

					$directory = $file;
					$result = [];
					$files = array_diff(scandir($directory), ['.','..']);

					foreach ($files as $entry) if (!$this->ce_is_entry_ignored($entry, $allow_show_folders, $hidden_patterns)) {

						$i = $directory . '/' . $entry;
						$path = preg_replace('@^\./@', '', $i);
						$mime_type = $this->ce_mime_type( $path );

						// Check if $path is viewable or not

						if ( ( strpos( $mime_type, 'text' ) !== false ) || ( strpos( $mime_type, 'php' ) !== false ) || ( strpos( $mime_type, 'json' ) !== false ) || ( strpos( $mime_type, 'html' ) !== false ) || ( strpos( $mime_type, 'empty' ) !== false ) ) {

							$is_viewable = true;

						} else {

							$is_viewable = false;						

						}

						// Check if $path is downloadable or not
						
						if ( is_dir( $path ) ) {

							$is_downloadable = false;

						} else {

							$is_downloadable = true;

						}

						// Check if $path is deletable or not

						if ( ( strpos( $path, 'index.php' ) !== false ) && ( strpos( $path, 'wp-content' ) === false ) ) {

							$is_deletable = false;

						} elseif ( ( strpos( $path, 'index.php' ) !== false ) && ( strpos( $path, 'wp-content' ) !== false ) ) {

							$is_deletable = true;

						} elseif ( $this->ce_is_wpcore_path( $path ) === false ) {

							$is_deletable = true;

						} else {

							$is_deletable = false;

						}

						$relpath = str_replace( ABSPATH, '', $i );
						$stat = stat($i);

						$result[] = [
							'mtime' => $stat['mtime'],
							'size' => $stat['size'],
							'name' => basename($i),
							'path' => $path,
							'relpath'	=> $relpath,
							'mime_type'	=> $mime_type,
							'is_dir' => is_dir($i),
							'is_viewable' => $is_viewable,
							'is_downloadable' => $is_downloadable,
							'is_deletable' => $allow_delete && ( (!is_dir($i) && is_writable( $directory ) ) || ( is_dir($i) && is_writable($directory) && $this->ce_is_recursively_deleteable($i) ) ) && $is_deletable,
							'deletion_nonce' => $deletion_nonce,
							'is_readable' => is_readable($i),
							'is_writable' => is_writable($i),
							'is_executable' => is_executable($i),
						];

					}

					usort($result,function($f1,$f2){
						$f1_key = ($f1['is_dir']?:2) . $f1['name'];
						$f2_key = ($f2['is_dir']?:2) . $f2['name'];
						return $f1_key > $f2_key;
					});

					echo json_encode([
						'success' => true, 
						'is_writable' => is_writable($file), 
						'abspath' => $abspath,
						'abspath_hash' => $abspath_hash,
						'results' =>$result
					]);
					exit;

				} else {

					echo json_encode([
						'success' => false,
						'abspath' => $abspath,
						'abspath_hash' => $abspath_hash,
						'error_message' => '/' . $relpath . ' does not exist.' 
					]);
					exit;

				}

			} elseif ( ( isset( $_GET['do'] ) ) && ( $_GET['do'] == 'view' ) ) {

				// Get file content and return default content when file is empty

				if ( empty( file_get_contents( $file ) ) ) {

					if ( $_GET['do'] == 'view' ) {

						$file_content = 'This file is empty';

					} elseif ( $_GET['do'] == 'edit' ) {

				        $file_content = 'Start editing here...';

					}

				} else {

			        $file_content = esc_textarea( file_get_contents( $file ) );

				}


		        $filename = '/' . str_replace( ABSPATH, '', $file );
		        $file_extension = pathinfo( $file, PATHINFO_EXTENSION );

		        switch ( $file_extension ) {

		        	case 'php':
		        		$language = 'php';
		        		break;

		        	case 'html':
		        		$language = 'markup';
		        		break;

		        	case 'xml':
		        		$language = 'markup';
		        		break;

		        	case 'svg':
		        		$language = 'markup';
		        		break;

		        	case 'js':
		        		$language = 'javascript';
		        		break;

		        	case 'css':
		        		$language = 'css';
		        		break;

		        	case 'md':
		        		$language = 'markdown';
		        		break;

		        	case 'json':
		        		$language = 'json';
		        		break;

		        	case 'lock':
		        		$language = 'json';
		        		break;

		        	case 'po':
		        		$language = 'markup';
		        		break;

		        	case 'txt':
		        		$language = 'markup';
		        		break;

		        	case '.htaccess':
		        		$language = 'markup';
		        		break;

		        	case '.nginx':
		        		$language = 'markup';
		        		break;

		        	case '':
		        		$language = 'markup';
		        		break;

		        }

		        if ( isset( $language ) ) {

		        	$code_class = ' class="language-' . $language . ' match-braces"';

		        } else {

		        	$code_class = ' class="match-braces"';

		        }

			} elseif ( ( isset( $_GET['do'] ) ) && ( $_GET['do'] == 'download' ) ) {

				// Process file download

				foreach( $disallowed_patterns as $pattern ) {

					if(fnmatch($pattern, $file)) {

						$this->err(403,"Files of this type are not allowed.");

					}

				}

				$filename = basename($file);
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$http_referer = sanitize_url( $_SERVER['HTTP_REFERER'] );

				header('Content-Type: ' . finfo_file($finfo, $file));
				header('Content-Length: '. filesize($file));
				header(sprintf('Content-Disposition: attachment; filename=%s',
					strpos('MSIE',$http_referer) ? rawurlencode($filename) : "\"$filename\"" ));
				ob_flush();
				readfile($file);

				exit;

			} elseif ( ( isset( $_POST['do'] ) ) && ( $_POST['do'] == 'delete' ) ) {

				// Delete file or folder (recursively)

				if( $allow_delete ) {

					$nonce = $_POST['nonce'];

					if ( !empty( $nonce ) && wp_verify_nonce( $nonce, 'deletion-nonce_' . $_COOKIE['_ce_xsrf'] . $uid ) ) {

						$this->ce_delete_recursively( $file );

						echo json_encode([
							'success' => true,
							'message' => 'Deletion was successful.'
						]);

					} else {

						echo json_encode([
							'success' => false,
							'message' => 'Deletion was not successful.'
						]);

					}

				}

				exit;

			} else {}

			// Output HTML

			$html_output = '';

			if ( ( ! isset( $_GET['do'] ) ) || ( ( isset( $_GET['do'] ) ) && ( $_GET['do'] == 'list' ) ) ) {

				$html_output .= '<div id="top">
									<div id="breadcrumb">&nbsp;</div>
								</div>
								<table id="table"><thead><tr>
									<th>Name</th>
									<th class="th-actions">Actions</th>
									<th>Size</th>
									<th>Modified</th>
									<th>Permissions</th>
								</tr></thead><tbody id="list"></tbody></table>';

			} elseif ( isset( $_GET['do'] ) && ( $_GET['do'] == 'view' ) ) {

				// Top part of file content

				$html_output .= '<div class="viewer-nav viewer-top">
										<a href="#" class="back-step" onclick="window.history.back()"><span>&#10132;</span>Back</a><span class="viewing">You are viewing <span class="filename">' . $filename . '</span></span>
								</div>';

				// File content viewer

				$html_output .= '<div id="viewer-content"><pre class="line-numbers"><code' . $code_class . '>'. $file_content .'</code></pre></div>';

				// Bottom part of file content

				$html_output .= '<div class="viewer-nav viewer-bottom">
										<a href="#" class="back-step" onclick="window.history.back()"><span>&#10132;</span>Back</a>
								</div>';

			} else {}


			if ( empty( $file_content ) ) {

			} else {



			}

			return $html_output;

		}

	}

	/**
	 * Get absolute path
	 *
	 * @link http://php.net/manual/en/function.realpath.php#84012
	 * @since 1.0.0
	 */
	public function ce_get_absolute_path( $path ) {

		$path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$parts = explode(DIRECTORY_SEPARATOR, $path);
		$absolutes = [];

		foreach ($parts as $part) {

		    if ('.' == $part) continue;

		    if ('..' == $part) {
		        array_pop($absolutes);
		    } else {
		        $absolutes[] = $part;
		    }

		}

		return implode(DIRECTORY_SEPARATOR, $absolutes);

	}

	/**
	 *	Check if entry is ignored
	 * 
	 * @since 1.0.0
	 */
	public function ce_is_entry_ignored($entry, $allow_show_folders, $hidden_patterns) {

		if ($entry === basename(__FILE__)) {
			return true;
		}

		if (is_dir($entry) && !$allow_show_folders) {
			return true;
		}

		foreach($hidden_patterns as $pattern) {

			if(fnmatch($pattern,$entry)) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Output error message
	 *
	 * @since 1.0.0
	 */
	public function ce_err($code,$msg) {
		// http_response_code($code);
		// header("Content-Type: application/json");
		// echo json_encode(['error' => ['code'=>intval($code), 'msg' => $msg]]);
		// exit;
		return 'Code: ' . $code . ' | Message: ' . $msg;
	}

	/**
	 * Output as Bytes
	 *
	 * @since 1.0.0
	 */
	public function ce_as_bytes($ini_v) {

		$ini_v = trim($ini_v);
		$s = ['g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10];

		return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);

	}

	/**
	 * Get mime type of file
	 *
	 * @link https://github.com/prasathmani/tinyfilemanager
	 * @since 1.0.0
	 */
	public function ce_mime_type( $file_path ) {

	    if ( function_exists('finfo_open') ) {

	        $finfo = finfo_open( FILEINFO_MIME_TYPE );
	        $mime = finfo_file( $finfo, $file_path );
	        finfo_close( $finfo );
	        return $mime;

	    } elseif ( function_exists( 'mime_content_type' ) ) {

	        return mime_content_type( $file_path );

	    } else {

	        return '--';

	    }

	}

	/** 
	 * Check if directory is recursively deletable
	 *
	 * @since 1.3.0
	 */
	public function ce_is_recursively_deleteable($d) {
		$stack = [$d];
		while($dir = array_pop($stack)) {
			if(!is_readable($dir) || !is_writable($dir))
				return false;
			$files = array_diff(scandir($dir), ['.','..']);
			foreach($files as $file) if(is_dir($file)) {
				$stack[] = "$dir/$file";
			}
		}
		return true;
	}

	/**
	 * Delete file or folder recursively
	 *
	 * @since 1.3.0
	 */
	public function ce_delete_recursively( $dir ) {

		if ( is_dir( $dir ) ) {

			$files = array_diff( scandir( $dir ), ['.','..'] );

			foreach ( $files as $file ) {

				self::ce_delete_recursively( "$dir/$file" );

			}

			rmdir( $dir );

		} else {

			unlink($dir);
		}

	}

	/** 
	 * Check if path is part of WP core folders and files
	 *
	 * @since 1.3.0
	 */
	public function ce_is_wpcore_path( $path ) {

		$disallowed_paths = array(
			ABSPATH . 'wp-admin',
			ABSPATH . 'wp-content',
			ABSPATH . 'wp-includes',
			ABSPATH . 'wp-content/plugins',
			ABSPATH . 'wp-content/themes',
			ABSPATH . 'wp-content/uploads',
			ABSPATH . 'wp-activate.php',
			ABSPATH . 'wp-blog-header.php',
			ABSPATH . 'wp-comments-post.php',
			ABSPATH . 'wp-config.php',
			ABSPATH . 'wp-cron.php',
			ABSPATH . 'wp-links-opml.php',
			ABSPATH . 'wp-load.php',
			ABSPATH . 'wp-login.php',
			ABSPATH . 'wp-mail.php',
			ABSPATH . 'wp-settings.php',
			ABSPATH . 'wp-signup.php',
			ABSPATH . 'wp-trackback.php',
			ABSPATH . 'xmlrpc.php',
		);

		if ( ( strpos( $path, 'wp-admin' ) !== false ) || ( strpos( $path, 'wp-includes' ) !== false ) || in_array( $path, $disallowed_paths ) ) {

			return true; // $path is part of WP core

		} else {

			return false; // $path is NOT part of WP core

		}

	}

	/**
	 * Add main page in wp-admin
	 *
	 * @since 1.0.0
	 */
	public function ce_main_page() {

		if ( class_exists( 'CSF' ) ) {

			// Set a unique slug-like ID

			$prefix = 'code-explorer';

			CSF::createOptions ( $prefix, array(

				'menu_title' 		=> 'Code Explorer',
				'menu_slug' 		=> 'code-explorer',
				'menu_type'			=> 'submenu',
				'menu_parent'		=> 'tools.php',
				'menu_position'		=> 1,
				'framework_title' 	=> 'Code Explorer <small>by <a href="https://bowo.io" target="_blank">bowo.io</a></small>',
				'framework_class' 	=> 'ce',
				'show_bar_menu' 	=> false,
				'show_search' 		=> false,
				'show_reset_all' 	=> false,
				'show_reset_section' => false,
				'show_form_warning' => false,
				'sticky_header'		=> true,
				'save_defaults'		=> true,
				'show_footer' 		=> false,
				'footer_credit'		=> '<a href="https://wordpress.org/plugins/code-explorer/" target="_blank">Code Explorer</a> (<a href="https://github.com/qriouslad/code-explorer" target="_blank">github</a>) is built with the <a href="https://github.com/devinvinson/WordPress-Plugin-Boilerplate/" target="_blank">WordPress Plugin Boilerplate</a>, <a href="https://wppb.me" target="_blank">wppb.me</a> and <a href="https://github.com/Codestar/codestar-framework" target="_blank">CodeStar</a>.',

			) );

			CSF::createSection( $prefix, array(

				'title'		=> 'The Code Explorer',
				'fields'	=> array(

					array(
						'type'	=> 'content',
						'title'	=> '',
						'class'	=> 'fmbody',
						'content'	=> $this->ce_index(),
					),

				),

			) );

		}

	}

	/**
	 * Add "Access Now" plugin action link
	 *
	 * @since 1.0.0
	 */
	public function ce_plugin_action_links( $links ) {

		$action_link = '<a href="tools.php?page=' . $this->plugin_name . '">Access Now</a>';

		array_unshift( $links, $action_link );

		return $links;

	}

	/**
	 * Register a submenu directly with WP core function
	 *
	 * @since 1.0.0
	 */
	public function ce_register_submenu() {

		add_submenu_page(
			'tools.php',
			'Code Explorer',
			'Code Explorer',
			'manage_options',
			'code-explorer',
			'ce_register_submenu_callback'
		);
	}

	/**
	 * Skeleton callback function for submenu registration
	 *
	 * @since 1.0.0
	 */
	public function ce_register_submenu_callback() {

		echo 'Nothing to show here...';

	}

	/**
	 * Remove CodeStar framework welcome / ads page
	 *
	 * @since 1.0.0
	 */
	public function ce_remove_codestar_submenu() {

		remove_submenu_page( 'tools.php', 'csf-welcome' );

	}

}
