<?php
add_action('admin_menu', 'qs_config_page');

function qs_admin_init() {
    global $wp_version;
    if ( !function_exists('is_multisite') && version_compare( $wp_version, '3.0', '<' ) ) {
		function qs_version_warning() {
            echo "
            <div id='qs-warning' class='updated fade'><p><strong>".sprintf(__('Quote Source %s requires WordPress 3.0 or higher.'), QUOTE_SOURCE_VERSION) ."</strong> ".sprintf(__('Please <a href="%s">upgrade WordPress</a> to a current version.'), 'http://codex.wordpress.org/Upgrading_WordPress'). "</p></div>
            ";
        }
        add_action('admin_notices', 'qs_version_warning'); 
        return; 
	}
}
add_action('admin_init', 'qs_admin_init');

function qs_config_page() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('Quote Source'), __('Quote Source'), 'manage_options', 'qs-config', 'qs_config');
	}
}

function qs_handle_bool($val, $default='true') {
	if($val == null || $val == '') {
		return 'false';
	}
	if(is_bool($val)) {
		return (string)$val;
	}
	switch (strtolower($val)) {
		case '1':
		case 'on':
        case 'true':
            return 'true';
            break;
		case '0':
		case 'off':
        case 'false':
            return 'false';
            break;
        default:
            return $default;
    }
}

function qs_config() {
	if(isset($_POST['submit'])) {
		if(function_exists('current_user_can') && !current_user_can('manage_options')) {
			die('You have no rights to access');
		}
		// =========================================
		$hex_color = $_POST['txt_text_color'];
		if(preg_match('/^[a-f0-9]{6}$/i', $hex_color)) {
			update_option('qs_text_color', $hex_color);
		} else {
			update_option('qs_text_color', 'FFFFFF');
		}
		// =========================================
		$qs_layout = (int)$_POST['radio_layout'];
		if($qs_layout <= 0 || $qs_layout > 3) {
			$qs_layout = 1;
		}
		update_option('qs_layout', $qs_layout);
		// =========================================
		$qs_mode_single_post 	= qs_handle_bool($_POST['qs_mode_single_post']	, 'true');
		$qs_mode_category 		= qs_handle_bool($_POST['qs_mode_category']		, 'false');
		$qs_mode_page 			= qs_handle_bool($_POST['qs_mode_page']			, 'false');
		$qs_mode_summary 		= qs_handle_bool($_POST['qs_mode_summary']		, 'false');
		
		update_option('qs_mode_single_post'	, $qs_mode_single_post);
		update_option('qs_mode_category'	, $qs_mode_category);
		update_option('qs_mode_page'		, $qs_mode_page);
		update_option('qs_mode_summary'		, $qs_mode_summary);
		
		echo '<h3>Update Successful</h3>';
		echo '<p>Settings are saved and applied.</p>';
		echo '<p><a href="plugins.php?page=qs-config">Back to Config page</a></p>';
	} else {
		// option page -- START
		$options = array();
		// setup default options
		if(!get_option('qs_mode_single_post')) {
			add_option('qs_mode_single_post', 'true');
			$options['qs_mode_single_post'] = 'true';
		} else {
			$options['qs_mode_single_post'] = qs_handle_bool(get_option('qs_mode_single_post'));
		}
		
		if(!get_option('qs_mode_category')) {
			add_option('qs_mode_category', 'false');
			$options['qs_mode_category'] = 'false';
		} else {
			$options['qs_mode_category'] = qs_handle_bool(get_option('qs_mode_category'));
		}
		
		if(!get_option('qs_mode_page')) {
			add_option('qs_mode_page', 'false');
			$options['qs_mode_page'] = 'false';
		} else {
			$options['qs_mode_page'] = qs_handle_bool(get_option('qs_mode_page'));
		}
		
		if(!get_option('qs_mode_summary')) {
			add_option('qs_mode_summary', 'false');
			$options['qs_mode_summary'] = 'false';
		} else {
			$options['qs_mode_summary'] = qs_handle_bool(get_option('qs_mode_summary'));
		}
		
		if(!get_option('qs_text_color')) {
			add_option('qs_text_color', '000000');
			$options['qs_text_color'] = '000000';
		} else {
			$options['qs_text_color'] = get_option('qs_text_color');
		}
		
		if(!get_option('qs_layout')) {
			add_option('qs_layout', '1');
			$options['qs_layout'] = '1';
		} else {
			$options['qs_layout'] = get_option('qs_layout');
		}
		/*
		Option List
		===================================================
		qs_mode_single_post	: toggle display in single post (default: true)
		qs_mode_category	: toggle display in categories  (default: false)
		qs_mode_page		: toggle display in page  		(default: false)
		qs_mode_summary		: toggle display in summary     (default: false)
		qs_text_color		: select text color				(default: 000000)
		qs_layout			: select layout					(default: 1)
		*/
?>
<form action="" method="POST" id="qs-config">
	<h2><?php _e('Quote Source Configuration'); ?></h2>
	<table width="650" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<td valign="top">Show quoted sources on:</td>
			<td>
				<table width="100%" border="0" cellpadding="5" cellspacing="0">
					<tr>
						<td valign="top"><input name="qs_mode_single_post" type="checkbox" value="true" <?php if($options['qs_mode_single_post'] == 'true') { echo 'checked="checked"'; } ?> /></td>
						<td>Single Post<br /><span class="quote_source_desc">the page after user clicks the title of your post</span></td>
					</tr>
					<tr>
						<td valign="top"><input name="qs_mode_category" type="checkbox" value="true" <?php if($options['qs_mode_category'] == 'true') { echo 'checked="checked"'; } ?> /></td>
						<td>Categories<br /><span class="quote_source_desc">the page after user clicks on one of your categories</span></td>
					</tr>
					<tr>
						<td valign="top"><input name="qs_mode_page" type="checkbox" value="true" <?php if($options['qs_mode_page'] == 'true') { echo 'checked="checked"'; } ?> /></td>
						<td>Pages<br /><span class="quote_source_desc">the page after user clicks your page</span></td>
					</tr>
					<tr>
						<td valign="top"><input name="qs_mode_summary" type="checkbox" value="true" <?php if($options['qs_mode_summary'] == 'true') { echo 'checked="checked"'; } ?> /></td>
						<td>Summary, Front Page and Older Posts<br /><span class="quote_source_desc">the page which lists out the posts</span></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" style="border-bottom: 1px solid #999; margin-bottom: 15px"><b>Look &amp; Feel</b></td>
		</tr>
		<tr>
			<td valign="top">Text Color</td>
			<td>#<input type="text" name="txt_text_color" maxlength="6" value="<?php echo $options['qs_text_color'] ?>" /><br />&nbsp;</td>
		</tr>
		<tr>
			<?php
			$site1_url = 'http://raptor.hk';
			$site1_title = 'Raptor Talks';
			$site2_url = 'http://raptor.hk/plugin-quote-source/';
			$site2_title = 'Quote Source Plugin in Raptor Talks';
			?>
			<td valign="top">Layout</td>
			<td>
				<table width="100%" border="0" cellpadding="5" cellspacing="0">
					<tr>
						<td width="30"><input type="radio" name="radio_layout" value="1" <?php if($options['qs_layout'] == '1') { echo 'checked="checked"'; } ?> /></td>
						<td>
							<fieldset style="border: 1px solid #999; padding: 10px;">
								<legend>Type A&nbsp;</legend>
								<?php echo $site1_title; ?>: <a href="<?php echo $site1_url; ?>" target="_blank" title="<?php echo $site1_title; ?>"><?php echo $site1_url; ?></a><br />
								<?php echo $site2_title; ?>: <a href="<?php echo $site2_url; ?>" target="_blank" title="<?php echo $site2_title; ?>"><?php echo $site2_url; ?></a><br />
							</fieldset>
						</td>
					</tr>
					<tr>
						<td><input type="radio" name="radio_layout" value="2" <?php if($options['qs_layout'] == '2') { echo 'checked="checked"'; } ?> /></td>
						<td>
							<fieldset style="border: 1px solid #999; padding: 10px;">
								<legend>Type B</legend>
								<ol>
									<li><a href="<?php echo $site1_url; ?>" target="_blank" title="<?php echo $site1_title; ?>"><?php echo $site1_title; ?></a></li>
									<li><a href="<?php echo $site2_url; ?>" target="_blank" title="<?php echo $site2_title; ?>"><?php echo $site2_title; ?></a></li>
								</ol>
							</fieldset>
						</td>
					</tr>
					<tr>
						<td><input type="radio" name="radio_layout" value="3" <?php if($options['qs_layout'] == '3') { echo 'checked="checked"'; } ?> /></td>
						<td>
							<fieldset style="border: 1px solid #999; padding: 10px;">
								<legend>Type C</legend>
								<ul style="list-style-type: square; list-style-position: inside;">
									<li style="padding-left: 5px"><a href="<?php echo $site1_url; ?>" target="_blank" title="<?php echo $site1_title; ?>"><?php echo $site1_title; ?></a></li>
									<li style="padding-left: 5px"><a href="<?php echo $site2_url; ?>" target="_blank" title="<?php echo $site2_title; ?>"><?php echo $site2_title; ?></a></li>
								</ul>
							</fieldset>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;<br /><input type="submit" name="submit" value="Apply Changes" /><br />&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" style="border-bottom: 1px solid #999; margin-bottom: 15px"><b>Layout when only 1 source quoted</b></td>
		</tr>
		<tr>
			<td colspan="2">
				<fieldset style="border: 1px solid #999; padding: 10px;">
					<legend>Example</legend>
					<p><i>Source</i> : <a href="<?php echo $site1_url; ?>" title="<?php echo $site1_title; ?>" target="_blank"><?php echo $site1_title; ?></a></p>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan="2"><hr />If you like Quote Source, please consider <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4M78TFRBFZUC">donate US$5</a> to us. Report bugs <a href="mailto:findme@raptor.hk?subject=Quote%20Source%20Report%20Bug">here</a>.</td>
		</tr>
	</table>
</form>

<?php
		// option page -- END
	}
}
?>