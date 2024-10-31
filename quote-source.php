<?php
/*
Plugin Name: Quote Source
Plugin URI: http://raptor.hk/plugin-quote-source/
Description: Quote your article source at the end of your post.
Version: 1.0.6
Author: Raptor
Author URI: http://raptor.hk/
License: License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

define('QUOTE_SOURCE_VERSION', '1.0.6');
define('QUOTE_WORDPRESS_FOLDER',$_SERVER['DOCUMENT_ROOT']);
define('QUOTE_THEME_FOLDER',str_replace("\\",'/',dirname(__FILE__)));
define('QUOTE_THEME_PATH','/' . substr(QUOTE_THEME_FOLDER,stripos(QUOTE_THEME_FOLDER,'wp-content')));

add_action('admin_init','quote_source_init');
if(is_admin()) {
	require_once(dirname( __FILE__ ) . '/qs-admin.php');
}

function quote_source_init() {
	wp_enqueue_style('quote_source_css', QUOTE_THEME_PATH . '/custom/meta.css');
	foreach (array('post','page') as $type) {
		add_meta_box('quote_source_box', 'Quote up to 3 sources', 'quote_source_setup', $type, 'normal', 'high');
	}
	add_action('save_post','quote_source_save');

}

function quote_source_setup() {
	global $post;
	// get post metadata
	$meta = get_post_meta($post->ID,'_quote_source',TRUE);
	// include setup box
	require_once('custom/meta.php');
	// create a custom nonce
	echo '<input type="hidden" name="quote_source_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
}

function quote_source_save($post_id) {
	// authentication checks
	// make sure data came from our meta box
	if (!wp_verify_nonce($_POST['quote_source_noncename'],__FILE__)) {
		return $post_id;
	}

	// check user permissions
	if ($_POST['post_type'] == 'page') {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} else {
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
	}
	// authentication passed, save data
	// var types
	// single: _quote_source[var]
	// array: _quote_source[var][]
	// grouped array: _quote_source[var_group][0][var_1], _quote_source[var_group][0][var_2]
	$current_data = get_post_meta($post_id, '_quote_source', TRUE);	
	$new_data = $_POST['_quote_source'];
	quote_source_clean($new_data);
	if ($current_data) {
		if (is_null($new_data)) {
			delete_post_meta($post_id,'_quote_source');
		} else {
			update_post_meta($post_id,'_quote_source',$new_data);
		}
	} elseif (!is_null($new_data)) {
		add_post_meta($post_id,'_quote_source',$new_data,TRUE);
	}
	return $post_id;
}

function quote_source_clean(&$arr) {
	if (is_array($arr)) {
		foreach ($arr as $i => $v) {
			if (is_array($arr[$i])) {
				quote_source_clean($arr[$i]);
				if (!count($arr[$i])) {
					unset($arr[$i]);
				}
			} else {
				if (trim($arr[$i]) == '') {
					unset($arr[$i]);
				}
			}
		}
		if (!count($arr)) {
			$arr = NULL;
		}
	}
}

function quote_source($content = '') {
	/*
	INPUT: the post contents
	OUTPUT: the post contents with quoted source at specified position & format
	*/
	global $post;
	$meta = get_post_meta($post->ID,'_quote_source',TRUE);
	
	$quotes = array();
	
	$src_cnt = 0;
	for($i = 0; $i < 3; $i++) {
		$tmp_url = $meta['quote'. ($i+1)];
		$tmp_title = $meta['quote' . ($i+1) .'_title'];
		
		if(!empty($tmp_url) || !empty($tmp_title)) {
			$quotes[$src_cnt] = array();
			$quotes[$src_cnt]['url'] = $tmp_url;
			$quotes[$src_cnt]['title'] = $tmp_title;
			$src_cnt++;
		}
	}
	if($src_cnt == 0) {
		return $content;
	}

	if((is_single() 	&& get_option('qs_mode_single_post', 'true') 	== 'true') || 
		(is_category() 	&& get_option('qs_mode_category', 'false') 		== 'true') || 
		(is_page() 		&& get_option('qs_mode_page', 'false') 			== 'true') || 
		((is_home() || is_archive() || is_paged()) && get_option('qs_mode_summary', 'false') == 'true')) {
		// count sources
		/*$src_cnt = 0;
		$src_selected = 0;
		if(!empty($s1_title) || !empty($s1)) {
			$src_selected = 1;
			$src_cnt++;
		}
		if(!empty($s2_title) || !empty($s2)) {
			$src_selected = 2;
			$src_cnt++;
		}
		if(!empty($s3_title) || !empty($s3)) {
			$src_selected = 3;
			$src_cnt++;
		}*/
		if($src_cnt == 1) {
			// have only 1 quote
			$content .= '<br /><hr noshade="noshade" />';
			$selected_link = $quotes[0]['url'];
			$selected_title = $quotes[0]['title'];
			$content .= '<p><i>Source</i> : ';
			if(!empty($selected_title)) {
				if(!empty($selected_link)) {
					$content .= '<a href="' . $selected_link . '" title="' . $selected_title . '" target="_blank">' . $selected_title . '</a>';
				} else {
					$content .= $selected_title;
				}
				$content .= PHP_EOL;
			} elseif(!empty($selected_link)) {
				$content .= '<a href="' . $selected_link . '" target="_blank">' . $selected_link . '</a>' . PHP_EOL;
			}
			$content .= '</p>';
		} else {
			$display_mode = (int)get_option('qs_layout', 1);
			$content .= '<hr noshade="noshade" />';
			$content .= '<p style="color: #' . get_option('qs_mode_single_post', '000000') . '"><b>Sources :</b></p>';
			switch($display_mode) {
				case 1:
					$content .= '<p>';
					$lines = array();
					foreach($quotes as $quote) {
						if(!empty($quote['title'])) {
							if(!empty($quote['url'])) {
								$lines[] = $quote['title'] . ': <a href="' . $quote['url'] . '" title="' . $quote['title'] . '" target="_blank">' . $quote['url'] . '</a>' . PHP_EOL;
							} else {
								$lines[] = $quote['title'];
							}
						} else {
							$lines[] = '<a href="' . $quote['url'] . '" target="_blank">' . $quote['url'] . '</a>' . PHP_EOL;
						}
					}
					$content .= implode('<br />', $lines);
					$content .= '</p>';
					break;
				case 2:
					$content .= '<ol>';
					$lines = array();
					foreach($quotes as $quote) {
						if(!empty($quote['title'])) {
							if(!empty($quote['url'])) {
								$lines[] = '<li><a href="' . $quote['url'] . '" title="' . $quote['title'] . '" target="_blank">' . $quote['url'] . '</a></li>' . PHP_EOL;
							} else {
								$lines[] = '<li>' . $quote['title'] . '</li>' . PHP_EOL;
							}
						} else {
							$lines[] = '<li><a href="' . $quote['url'] . '" target="_blank">' . $quote['url'] . '</a></li>' . PHP_EOL;
						}
					}
					$content .= implode('', $lines);
					$content .= '</ol>';
					break;
				case 3:
					$content .= '<ul>';
					$lines = array();
					foreach($quotes as $quote) {
						if(!empty($quote['title'])) {
							if(!empty($quote['url'])) {
								$lines[] = '<li><a href="' . $quote['url'] . '" title="' . $quote['title'] . '" target="_blank">' . $quote['url'] . '</a></li>' . PHP_EOL;
							} else {
								$lines[] = '<li>' . $quote['title'] . '</li>' . PHP_EOL;
							}
						} else {
							$lines[] = '<li><a href="' . $quote['url'] . '" target="_blank">' . $quote['url'] . '</a></li>' . PHP_EOL;
						}
					}
					$content .= implode('', $lines);
					$content .= '</ul>';
					break;
			}
		}
	}
	return $content;
}
add_filter('the_content','quote_source');
?>