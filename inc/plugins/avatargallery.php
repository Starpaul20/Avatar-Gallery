<?php
/**
 * Avatar Gallery
 * Copyright 2014 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(defined('THIS_SCRIPT'))
{
	if(THIS_SCRIPT == 'usercp.php')
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		$templatelist .= 'usercp_avatar_gallery,usercp_avatar_gallery_avatar,usercp_avatar_gallery_option';
	}
}

// Tell MyBB when to run the hooks
$plugins->add_hook("usercp_avatar_start", "avatargallery_usercp");
$plugins->add_hook("usercp_do_avatar_start", "avatargallery_usercp_submit");

$plugins->add_hook("admin_user_menu", "avatargallery_admin_menu");
$plugins->add_hook("admin_user_action_handler", "avatargallery_admin_action_handler");
$plugins->add_hook("admin_user_permissions", "avatargallery_admin_permissions");
$plugins->add_hook("admin_formcontainer_end", "avatargallery_admin_gallery_user");
$plugins->add_hook("admin_user_users_begin", "avatargallery_admin_user");
$plugins->add_hook("admin_user_users_edit_graph", "avatargallery_admin_user_javascript");
$plugins->add_hook("admin_tools_get_admin_log_action", "avatargallery_admin_adminlog");
$plugins->add_hook("admin_tools_system_health_output_chmod_list", "avatargallery_chmod");

// The information that shows up on the plugin manager
function avatargallery_info()
{
	global $lang;
	$lang->load("avatargallery", true);

	return array(
		"name"				=> $lang->avatargallery_info_name,
		"description"		=> $lang->avatargallery_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.4",
		"codename"			=> "avatargallery",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function avatargallery_activate()
{
	global $db;

	// Insert settings
	$query = $db->simple_select("settinggroups", "gid", "name='member'");
	$gid = $db->fetch_field($query, "gid");

	$insertarray = array(
		'name' => 'avatardir',
		'title' => 'Avatar Directory',
		'description' => 'The directory where your avatars are stored. These are used in the avatar list in the User CP.',
		'optionscode' => 'text',
		'value' => 'images/avatars',
		'disporder' => 25,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	rebuild_settings();

	// Insert templates
	$insert_array = array(
		'title'		=> 'usercp_avatar_gallery',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->change_avatar}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
	<tr>
		{$usercpnav}
		<td valign="top">
			{$avatar_error}
			<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
				<tr>
					<td class="thead"><strong>{$lang->change_avatar}</strong></td>
				</tr>
				<tr>
					<td class="tcat"><strong>{$lang->local_galleries}</strong></td>
				</tr>
				<tr>
					<td class="trow1" align="center">
						<form method="post" action="usercp.php">
							<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
							<input type="hidden" name="action" value="avatar" />
								<select name="gallery">
									{$galleries}
								</select>
							&nbsp;{$gobutton}
						</form>
					</td>
				</tr>
				<tr>
					<td class="tcat"><strong>{$lang->avatars_in_gallery}</strong></td>
				</tr>
				<tr>
					<td class="trow2">
						<form method="post" action="usercp.php">
						<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
						<input type="hidden" name="action" value="do_avatar" />
						<input type="hidden" name="gallery" value="{$gallery}" />
						<table width="100%" cellpadding="4">
							{$avatarlist}
						</table>
					</td>
				</tr>
			</table>
			<br />
			<div align="center">
				<input type="hidden" name="action" value="do_avatar" />
				<input type="submit" class="button" name="submit" value="{$lang->change_avatar}" />
			</div>
		</td>
	</tr>
</table>
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_avatar_gallery_avatar',
		'template'	=> $db->escape_string('<td width="20%" align="center"><label for="avatar-{$avatar}"><img src="{$avatarpath}" alt="{$avatar}" title="{$avatar}" /><br /><input type="radio" class="radio" name="avatar" value="{$avatar}" id="avatar-{$avatar}" /><strong>{$avatarname}</strong></td>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_avatar_gallery_option',
		'template'	=> $db->escape_string('<tr>
	<td class="tcat" colspan="2"><strong>{$lang->local_galleries}</strong></td>
</tr>
<tr>
	<td class="trow2"><strong>{$lang->gallery}</strong></td>
	<td class="trow2">
		<form method="post" action="usercp.php">
			<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
			<input type="hidden" name="action" value="avatar" />
			<select name="gallery">
			{$galleries}
			</select>
			&nbsp;{$gobutton}
		</form>
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'usercp_avatar_gallery_option_bit',
		'template'	=> $db->escape_string('<option value="{$dir}" {$selected}>{$friendlyname}</option>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	// Update templates
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_avatar", "#".preg_quote('<td class="tcat" colspan="2"><strong>{$lang->custom_avatar}')."#i", '{$avatargallery}<td class="tcat" colspan="2"><strong>{$lang->custom_avatar}');
	find_replace_templatesets("usercp_avatar", "#".preg_quote('<form enctype="multipart/form-data" action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />')."#i", '');
	find_replace_templatesets("usercp_avatar", "#".preg_quote('{$avatarupload}')."#i", '<form enctype="multipart/form-data" action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
{$avatarupload}');

	change_admin_permission('user', 'avatar_gallery');
}

// This function runs when the plugin is deactivated.
function avatargallery_deactivate()
{
	global $db;
	$db->delete_query("settings", "name IN('avatardir')");
	$db->delete_query("templates", "title IN('usercp_avatar_gallery','usercp_avatar_gallery_avatar','usercp_avatar_gallery_option','usercp_avatar_gallery_option_bit')");
	rebuild_settings();

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("usercp_avatar", "#".preg_quote('{$avatargallery}')."#i", '', 0);
	find_replace_templatesets("usercp_avatar", "#".preg_quote('{$header}
')."#i", '{$header}
<form enctype="multipart/form-data" action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />', 0);
	find_replace_templatesets("usercp_avatar", "#".preg_quote('<form enctype="multipart/form-data" action="usercp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
{$avatarupload}')."#i", '{$avatarupload}', 0);

	change_admin_permission('user', 'avatar_gallery', -1);
}

// User CP gallery page
function avatargallery_usercp()
{
	global $db, $mybb, $templates, $theme, $lang, $avatargallery, $galleries, $gobutton, $gallerylist, $headerinclude, $header, $usercpnav, $footer, $avatar_error;
	$lang->load("avatargallery");

	// Get a listing of available galleries
	$gallerylist['default'] = $lang->default_gallery;
	$avatardir = @opendir($mybb->settings['avatardir']);
	while($dir = @readdir($avatardir))
	{
		if(is_dir($mybb->settings['avatardir']."/$dir") && substr($dir, 0, 1) != ".")
		{
			$gallerylist[$dir] = str_replace("_", " ", $dir);
		}
	}
	@closedir($avatardir);
	natcasesort($gallerylist);
	reset($gallerylist);
	$galleries = $selected = '';
	foreach($gallerylist as $dir => $friendlyname)
	{
		if($dir == $mybb->get_input('gallery'))
		{
			$activegallery = $friendlyname;
			$selected = "selected=\"selected\"";
		}

		eval("\$galleries .= \"".$templates->get("usercp_avatar_gallery_option_bit")."\";");
		$selected = '';
	}

	eval("\$avatargallery = \"".$templates->get("usercp_avatar_gallery_option")."\";");

	// Check to see if we're in a gallery or not
	if(isset($activegallery))
	{
		$gallery = str_replace("..", "", $mybb->get_input('gallery'));
		$lang->avatars_in_gallery = $lang->sprintf($lang->avatars_in_gallery, $activegallery);
		// Get a listing of avatars in this gallery
		$avatardir = $mybb->settings['avatardir'];
		if($gallery != "default")
		{
			$avatardir .= "/$gallery";
		}
		$opendir = opendir($avatardir);
		while($avatar = @readdir($opendir))
		{
			$avatarpath = $avatardir."/".$avatar;
			if(is_file($avatarpath) && preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $avatar))
			{
				$avatars[] = $avatar;
			}
		}
		@closedir($opendir);

		if(is_array($avatars))
		{
			natcasesort($avatars);
			reset($avatars);
			$count = 0;
			$avatarlist = "<tr>\n";
			foreach($avatars as $avatar)
			{
				$avatarpath = $avatardir."/".$avatar;
				$avatarname = preg_replace("#\.(jpg|jpeg|gif|bmp|png)$#i", "", $avatar);
				$avatarname = ucwords(str_replace("_", " ", $avatarname));
				if($mybb->user['avatar'] == $avatarpath)
				{
					$checked = "checked=\"checked\"";
				}
				if($count == 5)
				{
					$avatarlist .= "</tr>\n<tr>\n";
					$count = 0;
				}
				++$count;
				eval("\$avatarlist .= \"".$templates->get("usercp_avatar_gallery_avatar")."\";");
			}
			if($count != 0)
			{
				for($i = $count; $i <= 5; ++$i)
				{
					eval("\$avatarlist .= \"".$templates->get("usercp_avatar_gallery_blankblock")."\";");
				}
			}
		}
		else
		{
			eval("\$avatarlist = \"".$templates->get("usercp_avatar_gallery_noavatars")."\";");
		}

		eval("\$gallery = \"".$templates->get("usercp_avatar_gallery")."\";");
		output_page($gallery);
		exit;
	}
}

// User CP submit page
function avatargallery_usercp_submit()
{
	global $db, $mybb, $lang, $plugins;
	$lang->load("avatargallery");

	if(!empty($mybb->input['gallery'])) // Gallery avatar
	{
		require_once MYBB_ROOT."inc/functions_upload.php";
		$avatar_error = "";

		if(empty($mybb->input['avatar']))
		{
			$avatar_error = $lang->error_noavatar;
		}

		$mybb->input['gallery'] = str_replace(array("./", ".."), "", $mybb->get_input('gallery'));
		$mybb->input['avatar'] = str_replace(array("./", ".."), "", $mybb->get_input('avatar'));

		if(empty($avatar_error))
		{
			if($mybb->input['gallery'] == "default")
			{
				$avatarpath = $db->escape_string($mybb->settings['avatardir']."/".$mybb->input['avatar']);
			}
			else
			{
				$avatarpath = $db->escape_string($mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar']);
			}

			if(file_exists($avatarpath))
			{
				$dimensions = @getimagesize($avatarpath);

				$updated_avatar = array(
					"avatar" => $avatarpath.'?dateline='.TIME_NOW,
					"avatardimensions" => "{$dimensions[0]}|{$dimensions[1]}",
					"avatartype" => "gallery"
				);
				$db->update_query("users", $updated_avatar, "uid='".$mybb->user['uid']."'");
			}
			remove_avatars($mybb->user['uid']);
		}

		if(empty($avatar_error))
		{
			$plugins->run_hooks("usercp_do_avatar_end");
			redirect("usercp.php?action=avatar", $lang->redirect_avatarupdated);
		}
		else
		{
			$lang->error_invalidavatarurl = $lang->error_noavatar;
			$lang->error_remote_avatar_not_allowed = $lang->error_noavatar;
			$avatar_error = inline_error($avatar_error);
			$mybb->input['action'] = "avatar";
		}
	}
}

// Admin CP avatar gallery page
function avatargallery_admin_menu($sub_menu)
{
	global $lang;
	$lang->load("user_avatar_gallery");

	$sub_menu['110'] = array('id' => 'avatar_gallery', 'title' => $lang->avatar_gallery, 'link' => 'index.php?module=user-avatar_gallery');

	return $sub_menu;
}

function avatargallery_admin_action_handler($actions)
{
	$actions['avatar_gallery'] = array('active' => 'avatar_gallery', 'file' => 'avatar_gallery.php');

	return $actions;
}

function avatargallery_admin_permissions($admin_permissions)
{
	global $lang;
	$lang->load("user_avatar_gallery");

	$admin_permissions['avatar_gallery'] = $lang->can_manage_avatar_gallery;

	return $admin_permissions;
}

// Admin CP editing user avatar
function avatargallery_admin_gallery_user()
{
	global $lang, $user, $form_container, $run_module;
	$lang->load("user_avatar_gallery");

	if($run_module == 'user' && !empty($form_container->_title) && !empty($lang->specify_custom_avatar) && $form_container->_title == $lang->specify_custom_avatar)
	{
		// Select an image from the gallery
		echo "<div class=\"border_wrapper\">";
		echo "<div class=\"title\">{$lang->select_avatar_gallery}</div>";
		echo "<iframe src=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}\" width=\"100%\" height=\"350\" frameborder=\"0\"></iframe>";
		echo "</div>";
	}
}

// Avatar Gallery when editing user
function avatargallery_admin_user()
{
	global $db, $mybb, $lang, $page;
	$lang->load("user_avatar_gallery");

	if($mybb->input['action'] == "avatar_gallery")
	{
		$uid = $mybb->get_input('uid', MyBB::INPUT_INT);
		$user = get_user($uid);
		if(!$user['uid'])
		{
			exit;
		}

		// We've selected a new avatar for this user!
		if($mybb->get_input('avatar'))
		{
			if(!verify_post_check($mybb->get_input('my_post_key')))
			{
				echo $lang->invalid_post_verify_key2;
				exit;
			}

			$mybb->input['avatar'] = str_replace(array("./", ".."), "", $mybb->get_input('avatar'));

			if(file_exists("../".$mybb->settings['avatardir']."/".$mybb->input['avatar']))
			{
				$dimensions = @getimagesize("../".$mybb->settings['avatardir']."/".$mybb->input['avatar']);
				$updated_avatar = array(
					"avatar" => $db->escape_string($mybb->settings['avatardir']."/".$mybb->input['avatar'].'?dateline='.TIME_NOW),
					"avatardimensions" => "{$dimensions[0]}|{$dimensions[1]}",
					"avatartype" => "gallery"
				);

				$db->update_query("users", $updated_avatar, "uid='".$user['uid']."'");

				// Log admin action
				log_admin_action($user['uid'], $user['username']);
			}
			remove_avatars($user['uid']);
			// Now a tad of javascript to submit the parent window form
			echo "<script type=\"text/javascript\">window.parent.submitUserForm();</script>";
			exit;
		}

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
		echo "<head profile=\"http://gmpg.org/xfn/1\">\n";
		echo "	<title>{$lang->avatar_gallery}</title>\n";
		echo "	<link rel=\"stylesheet\" href=\"styles/".$page->style."/main.css?ver=1804\" type=\"text/css\" />\n";
		echo "	<link rel=\"stylesheet\" href=\"styles/".$page->style."/avatar_gallery.css\" type=\"text/css\" />\n";
		echo "	<script type=\"text/javascript\" src=\"../jscripts/jquery.js\"></script>\n";
		echo "	<script type=\"text/javascript\" src=\"../jscripts/jquery.plugins.min.js\"></script>\n";
		echo "	<script type=\"text/javascript\" src=\"../jscripts/general.js\"></script>\n";
		echo "</head>\n";
		echo "<body id=\"avatar_gallery\">\n";

		// Sanitize incoming path if we have one
		$gallery = str_replace(array("..", "\x0"), "", $mybb->get_input('gallery'));

		$breadcrumb = "<a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}\">Default Gallery</a>";

		$mybb->settings['avatardir'] = "../".$mybb->settings['avatardir'];

		if(!is_dir($mybb->settings['avatardir']) && is_dir(MYBB_ROOT."/images/avatars/"))
		{
			$mybb->settings['avatardir'] = "../images/avatars/";
		}

		// Within a gallery
		if(!empty($gallery))
		{
			$path = $gallery."/";
			$real_path = $mybb->settings['avatardir']."/".$path;
			if(is_dir($real_path))
			{
				// Build friendly gallery breadcrumb
				$breadcrumb_url = '';
				$gallery_path = explode("/", $gallery);
				foreach($gallery_path as $key => $url_bit)
				{
					if($breadcrumb_url) $breadcrumb_url .= "/";
					$breadcrumb_url .= $url_bit;
					$gallery_name = str_replace(array("_", "%20"), " ", $url_bit);
					$gallery_name = ucwords($gallery_name);

					if(isset($gallery_path[$key+1]))
					{
						$breadcrumb .= " &raquo; <a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;gallery={$breadcrumb_url}\">{$gallery_name}</a>";
					}
					else
					{
						$breadcrumb .= " &raquo; {$gallery_name}";
					}
				}
			}
			else
			{
				exit;
			}
		}
		else
		{
			$path = "";
			$real_path = $mybb->settings['avatardir'];
		}

		// Get a listing of avatars/directories within this gallery
		$sub_galleries = $avatars = array();
		$files = @scandir($real_path);

		if(is_array($files))
		{
			foreach($files as $file)
			{
				if($file == "." || $file == ".." || $file == ".svn")
				{
					continue;
				}

				// Build friendly name
				$friendly_name = str_replace(array("_", "%20"), " ", $file);
				$friendly_name = ucwords($friendly_name);
				if(is_dir($real_path."/".$file))
				{
					// Only add this gallery if there are avatars or galleries inside it (no empty directories!)
					$has = 0;
					$dh = @opendir($real_path."/".$file);
					while(false !== ($sub_file = readdir($dh)))
					{
						if(preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $sub_file) || is_dir($real_path."/".$file."/".$sub_file))
						{
							$has = 1;
							break;
						}
					}
					@closedir($dh);
					if($has == 1)
					{
						$sub_galleries[] = array(
							"path" => $path.$file,
							"friendly_name" => $friendly_name
						);
					}
				}
				else if(preg_match("#\.(jpg|jpeg|gif|bmp|png)$#i", $file))
				{
					$friendly_name = preg_replace("#\.(jpg|jpeg|gif|bmp|png)$#i", "", $friendly_name);

					// Fetch dimensions
					$dimensions = @getimagesize($real_path."/".$file);

					$avatars[] = array(
						"path" => $path.$file,
						"friendly_name" => $friendly_name,
						"width" => $dimensions[0],
						"height" => $dimensions[1]
					);
				}
			}
		}

		require_once MYBB_ROOT."inc/functions_image.php";

		// Now we're done, we can simply show our gallery page
		echo "<div id=\"gallery_breadcrumb\">{$breadcrumb}</div>\n";
		echo "<div id=\"gallery\">\n";
		echo "<ul id=\"galleries\">\n";
		if(is_array($sub_galleries))
		{
			foreach($sub_galleries as $gallery)
			{
				if(!isset($gallery['thumb']))
				{
					$gallery['thumb'] = "styles/{$page->style}/images/avatar_gallery.png";
					$gallery['thumb_width'] = 64;
					$gallery['thumb_height'] = 64;
				}
				else
				{
					$gallery['thumb'] = "{$mybb->settings['avatardir']}/{$gallery['thumb']}";
				}
				$scaled_dimensions = scale_image($gallery['thumb_width'], $gallery['thumb_height'], 80, 80);
				$top = ceil((80-$scaled_dimensions['height'])/2);
				$left = ceil((80-$scaled_dimensions['width'])/2);
				echo "<li><a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;gallery={$gallery['path']}\"><span class=\"image\"><img src=\"{$gallery['thumb']}\" alt=\"\" style=\"margin-top: {$top}px;\" height=\"{$scaled_dimensions['height']}\" width=\"{$scaled_dimensions['width']}\"></span><span class=\"title\">{$gallery['friendly_name']}</span></a></li>\n";
			}
		}
		echo "</ul>\n";
		// Build the list of any actual avatars we have
		echo "<ul id=\"avatars\">\n";
		if(is_array($avatars))
		{
			foreach($avatars as $avatar)
			{
				$scaled_dimensions = scale_image($avatar['width'], $avatar['height'], 80, 80);
				$top = ceil((80-$scaled_dimensions['height'])/2);
				$left = ceil((80-$scaled_dimensions['width'])/2);
				echo "<li><a href=\"index.php?module=user-users&amp;action=avatar_gallery&amp;uid={$user['uid']}&amp;avatar={$avatar['path']}&amp;my_post_key={$mybb->post_code}\"><span class=\"image\"><img src=\"{$mybb->settings['avatardir']}/{$avatar['path']}\" alt=\"\" style=\"margin-top: {$top}px;\" height=\"{$scaled_dimensions['height']}\" width=\"{$scaled_dimensions['width']}\" /></span><span class=\"title\">{$avatar['friendly_name']}</span></a></li>\n";
			}
		}
		echo "</ul>\n";
		echo "</div>";
		echo "</body>";
		echo "</html>";
		exit;
	}
}

// Avatar gallery user submit form javascript
function avatargallery_admin_user_javascript()
{
	echo "<script type=\"text/javascript\">\n function submitUserForm() { $('#tab_overview').closest('FORM').submit(); }</script>\n";
}

// Admin Log display
function avatargallery_admin_adminlog($plugin_array)
{
	global $lang;
	$lang->load("user_avatar_gallery");

	if($plugin_array['lang_string'] == 'admin_log_user_avatar_gallery_add_avatar')
	{
		if(!$plugin_array['logitem']['data'][1])
		{
			$plugin_array['lang_string'] = 'admin_log_user_avatar_gallery_add_avatar_default';
		}
	}

	elseif($plugin_array['lang_string'] == 'admin_log_user_avatar_gallery_edit_avatar')
	{
		if(!$plugin_array['logitem']['data'][1])
		{
			$plugin_array['lang_string'] = 'admin_log_user_avatar_gallery_edit_avatar_default';
		}
	}

	elseif($plugin_array['lang_string'] == 'admin_log_user_avatar_gallery_delete_avatar')
	{
		if(!$plugin_array['logitem']['data'][1])
		{
			$plugin_array['lang_string'] = 'admin_log_user_avatar_gallery_delete_avatar_default';
		}
	}

	return $plugin_array;
}

// Check to see if CHMOD for avatar galleries is writable
function avatargallery_chmod()
{
	global $lang, $table, $avatar_themes;
	$lang->load("user_avatar_gallery");

	if(is_writable(MYBB_ROOT.'/images/avatars/'))
	{
		$avatar_themes = "<span style=\"color: green;\">{$lang->writable}</span>";
	}
	else
	{
		$avatar_themes = "<strong><span style=\"color: #C00\">{$lang->not_writable}</span></strong><br />{$lang->please_chmod_777}";
		++$errors;
	}

	$table->construct_cell("<strong>{$lang->avatar_gallery_directory}</strong>");
	$table->construct_cell('./images/avatars');
	$table->construct_cell($avatar_themes);
	$table->construct_row();
}
