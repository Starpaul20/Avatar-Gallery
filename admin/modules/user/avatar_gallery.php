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

require_once MYBB_ROOT."inc/functions_upload.php";

if(!$mybb->settings['avatardir'])
{
	$mybb->settings['avatardir'] = 'images/avatars';
}

$page->add_breadcrumb_item($lang->avatar_gallery, "index.php?module=user-avatar_gallery");

if($mybb->input['action'] == "add_gallery" || $mybb->input['action'] == "add_avatar" || !$mybb->input['action'])
{
	$sub_tabs['avatar_gallery'] = array(
		'title' => $lang->avatar_gallery,
		'link' => "index.php?module=user-avatar_gallery",
		'description' => $lang->avatar_gallery_desc
	);

	$sub_tabs['add_gallery'] = array(
		'title' => $lang->add_gallery,
		'link' => "index.php?module=user-avatar_gallery&amp;action=add_gallery",
		'description' => $lang->add_gallery_desc
	);

	$sub_tabs['add_avatar'] = array(
		'title' => $lang->add_avatar,
		'link' => "index.php?module=user-avatar_gallery&amp;action=add_avatar",
		'description' => $lang->add_avatar_desc
	);
}

if($mybb->input['action'] == "add_gallery")
{
	if($mybb->request_method == "post")
	{
		$mybb->input['name'] = htmlspecialchars_uni($mybb->get_input('name'));

		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']) && my_strlen($mybb->input['name']) > 1)
		{
			$errors[] = $lang->error_gallery_exists;
		}

		if(!$errors)
		{
			@mkdir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']);

			// Log admin action
			log_admin_action($mybb->input['name']);

			flash_message($lang->success_gallery_created, 'success');
			admin_redirect("index.php?module=user-avatar_gallery");
		}
	}

	$page->add_breadcrumb_item($lang->add_gallery);
	$page->output_header($lang->avatar_gallery." - ".$lang->add_gallery);

	$page->output_nav_tabs($sub_tabs, 'add_gallery');
	$form = new Form("index.php?module=user-avatar_gallery&amp;action=add_gallery", "post");

	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->add_gallery);
	$form_container->output_row($lang->gallery_name."<em>*</em>", $lang->gallery_name_desc, $form->generate_text_box('name', $mybb->get_input('name'), array('id' => 'name')), 'name');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_gallery);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit_gallery")
{
	$mybb->input['gallery'] = htmlspecialchars_uni($mybb->get_input('gallery'));

	if(!is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']))
	{
		flash_message($lang->error_invalid_gallery, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if($mybb->request_method == "post")
	{
		$mybb->input['name'] = htmlspecialchars_uni($mybb->get_input('name'));

		if(!trim($mybb->input['name']))
		{
			$errors[] = $lang->error_missing_name;
		}

		if(is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']) && my_strlen($mybb->input['name']) > 1)
		{
			$errors[] = $lang->error_gallery_exists;
		}

		if(!$errors)
		{
			@rename(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery'], MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']);

			$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['gallery'];

			$query = $db->simple_select("users", "uid, avatar", "avatar LIKE '%{$oldavatar}%' AND avatartype='gallery'");
			while($user = $db->fetch_array($query))
			{
				$user['avatar'] = substr($user['avatar'], 0, -20);
				$newavatar = str_replace($mybb->settings['avatardir']."/".$mybb->input['gallery'], $mybb->settings['avatardir']."/".$mybb->input['name'], $user['avatar']);

				$update_avatar = array(
					"avatar" => $db->escape_string($newavatar."?dateline=".TIME_NOW)
				);

				$db->update_query("users", $update_avatar, "uid='{$user['uid']}'");
			}

			// Log admin action
			log_admin_action($mybb->input['name']);

			flash_message($lang->success_gallery_updated, 'success');
			admin_redirect("index.php?module=user-avatar_gallery");
		}
	}

	$page->add_breadcrumb_item($lang->edit_gallery);
	$page->output_header($lang->avatar_gallery." - ".$lang->edit_gallery);

	$sub_tabs['edit_gallery'] = array(
		'title'	=> $lang->edit_gallery,
		'link'	=> "index.php?module=user-avatar_gallery",
		'description'	=> $lang->edit_gallery_desc
	);

	$page->output_nav_tabs($sub_tabs, 'edit_gallery');
	$form = new Form("index.php?module=user-avatar_gallery&amp;action=edit_gallery", "post", "edit");
	echo $form->generate_hidden_field("gallery", $mybb->input['gallery']);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['name'] = $mybb->input['gallery'];
	}

	$form_container = new FormContainer($lang->edit_gallery);
	$form_container->output_row($lang->gallery_name."<em>*</em>", $lang->gallery_name_desc, $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_gallery);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete_gallery")
{
	$mybb->input['gallery'] = htmlspecialchars_uni($mybb->get_input('gallery'));

	if(!is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']))
	{
		flash_message($lang->error_invalid_gallery, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if($mybb->request_method == "post")
	{
		$avatars = @opendir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']);
		while($file = @readdir($avatars))
		{
			if($file != ".." && $file != ".")
			{
				@unlink(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$file);
			}
		}
		@closedir($avatars);

		@rmdir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']);

		$avatar = $mybb->settings['avatardir']."/".$mybb->input['gallery'];

		$query = $db->simple_select("users", "uid", "avatar LIKE '%{$avatar}%' AND avatartype='gallery'");
		while($user = $db->fetch_array($query))
		{
			$update_avatar = array(
				"avatar" => "",
				"avatardimensions" => "",
				"avatartype" => ""
			);

			$db->update_query("users", $update_avatar, "uid='{$user['uid']}'");
		}

		// Log admin action
		log_admin_action($mybb->input['gallery']);

		flash_message($lang->success_gallery_deleted, 'success');
		admin_redirect("index.php?module=user-avatar_gallery");
	}
	else
	{
		$page->output_confirm_action("index.php?module=user-avatar_gallery&amp;action=delete_gallery&amp;gallery={$mybb->input['gallery']}", $lang->confirm_gallery_deletion);
	}
}

if($mybb->input['action'] == "add_avatar")
{
	$mybb->input['gallery'] = htmlspecialchars_uni($mybb->get_input('gallery'));

	if($mybb->request_method == "post")
	{
		if(!is_uploaded_file($_FILES['avatarfile']['tmp_name']))
		{
			$errors[] = $lang->error_missing_file;
		}

		$ext = get_extension(my_strtolower($_FILES['avatarfile']['name']));
		if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext))
		{
			$errors[] = $lang->error_invalid_extension;
		}

		$filename = $_FILES['avatarfile']['name'];
		if($mybb->input['gallery'])
		{
			$directory = $mybb->input['gallery'];
			if(is_file("../".$mybb->settings['avatardir']."/".$directory."/".$filename))
			{
				$errors[] = $lang->error_avatar_exists;
			}
		}
		else
		{
			if(is_file("../".$mybb->settings['avatardir']."/".$filename))
			{
				$errors[] = $lang->error_avatar_exists;
			}
		}

		if($mybb->input['gallery'])
		{
			$directory = $mybb->input['gallery'];
			if(!is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$directory))
			{
				$errors[] = $lang->error_gallery_does_not_exist;
			}
		}

		if(!$errors)
		{
			if($mybb->input['gallery'])
			{
				$directory = $mybb->input['gallery'];
				$file_avatar = upload_file($_FILES['avatarfile'], MYBB_ROOT."/".$mybb->settings['avatardir']."/".$directory, $_FILES['avatarfile']['name']);
			}
			else
			{
				$file_avatar = upload_file($_FILES['avatarfile'], MYBB_ROOT."/".$mybb->settings['avatardir'], $_FILES['avatarfile']['name']);
			}

			// Log admin action
			log_admin_action($file_avatar['filename'], $mybb->input['gallery']);

			if($mybb->input['gallery'])
			{
				$gallery_url = "&amp;gallery=".$mybb->input['gallery'];
			}
			else
			{
				$gallery_url = "";
			}

			flash_message($lang->success_avatar_uploaded, 'success');
			admin_redirect("index.php?module=user-avatar_gallery".$gallery_url);
		}
	}

	$page->add_breadcrumb_item($lang->add_avatar);
	$page->output_header($lang->avatar_gallery." - ".$lang->add_avatar);

	$page->output_nav_tabs($sub_tabs, 'add_avatar');
	$form = new Form("index.php?module=user-avatar_gallery&amp;action=add_avatar", "post", false, true);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input['gallery'] == 0;
	}

	$form_container = new FormContainer($lang->add_avatar);
	$form_container->output_row($lang->avatar_file."<em>*</em>", $lang->avatar_file_desc, $form->generate_file_upload_box("avatarfile", array('id' => 'avatarfile')), 'avatarfile');

	$gallery_list = array();
	$gallery_list[0] = $lang->default_gallery;
	$gallery_dir = scandir(MYBB_ROOT."/".$mybb->settings['avatardir']."/");
	foreach($gallery_dir as $gallery)
	{
		if($gallery != ".." && $gallery != ".")
		{
			if(is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$gallery))
			{
				$gallery_list[$gallery] = str_replace("_", " ", $gallery);
			}
		}
	}

	$form_container->output_row($lang->gallery."<em>*</em>", $lang->gallery_desc, $form->generate_select_box('gallery', $gallery_list, $mybb->input['gallery'], array('id' => 'gallery')), 'gallery');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_avatar);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit_avatar")
{
	$mybb->input['gallery'] = htmlspecialchars_uni($mybb->get_input('gallery'));
	$mybb->input['avatar'] = htmlspecialchars_uni($mybb->get_input('avatar'));
	$ext = get_extension($mybb->input['avatar']);

	if(!is_file(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar']))
	{
		flash_message($lang->error_invalid_avatar, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext))
	{
		flash_message($lang->error_not_avatar, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if($mybb->request_method == "post")
	{
		if($mybb->input['name'] != $mybb->input['gallery'])
		{
			if(is_file("../".$mybb->settings['avatardir']."/".$mybb->input['name']."/".$mybb->input['avatarname'].".".$ext))
			{
				$errors[] = $lang->error_avatar_exists;
			}
		}
		else
		{
			if(is_file("../".$mybb->settings['avatardir']."/".$mybb->input['avatarname']))
			{
				$errors[] = $lang->error_avatar_exists;
			}
		}

		if($mybb->input['name'])
		{
			if(!is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']))
			{
				$errors[] = $lang->error_gallery_does_not_exist;
			}
		}

		if(!$errors)
		{
			if($mybb->input['name'])
			{
				@rename(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'], MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['name']."/".$mybb->input['avatarname'].".".$ext);

				if($mybb->input['gallery'])
				{
					$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'];
				}
				else
				{
					$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['avatar'];
				}

				$newavatar = $mybb->settings['avatardir']."/".$mybb->input['name']."/".$mybb->input['avatarname'].".".$ext."?dateline=".TIME_NOW;
			}
			elseif($mybb->input['gallery'] && !$mybb->input['name'])
			{
				@rename(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'], MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['avatarname'].".".$ext);

				$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'];
				$newavatar = $mybb->settings['avatardir']."/".$mybb->input['avatarname'].".".$ext."?dateline=".TIME_NOW;
			}
			else
			{
				@rename(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['avatar'], MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['avatarname'].".".$ext);

				if($mybb->input['gallery'])
				{
					$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'];
				}
				else
				{
					$oldavatar = $mybb->settings['avatardir']."/".$mybb->input['avatar'];
				}

				$newavatar = $mybb->settings['avatardir']."/".$mybb->input['avatarname'].".".$ext."?dateline=".TIME_NOW;
			}

			$query = $db->simple_select("users", "uid, avatar", "avatar LIKE '%{$oldavatar}%' AND avatartype='gallery'");
			while($user = $db->fetch_array($query))
			{
				$update_avatar = array(
					"avatar" => $db->escape_string($newavatar)
				);

				$db->update_query("users", $update_avatar, "uid='{$user['uid']}'");
			}

			// Log admin action
			log_admin_action($mybb->input['avatarname'].".".$ext, $mybb->input['name']);

			if($mybb->input['name'])
			{
				$gallery_url = "&amp;gallery=".$mybb->input['name'];
			}
			else
			{
				$gallery_url = "";
			}

			flash_message($lang->success_avatar_updated, 'success');
			admin_redirect("index.php?module=user-avatar_gallery".$gallery_url);
		}
	}

	$page->add_breadcrumb_item($lang->edit_avatar);
	$page->output_header($lang->avatar_gallery." - ".$lang->edit_avatar);

	$sub_tabs['edit_avatar'] = array(
		'title'	=> $lang->edit_avatar,
		'link'	=> "index.php?module=user-avatar_gallery",
		'description'	=> $lang->edit_avatar_desc
	);

	$page->output_nav_tabs($sub_tabs, 'edit_avatar');
	$form = new Form("index.php?module=user-avatar_gallery&amp;action=edit_avatar", "post", "edit");
	echo $form->generate_hidden_field("avatar", $mybb->input['avatar']);
	echo $form->generate_hidden_field("gallery", $mybb->input['gallery']);

	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$ext = get_extension($mybb->input['avatar']);
		if($mybb->input['gallery'])
		{
			$mybb->input['name'] = $mybb->input['gallery'];
		}
		$mybb->input['avatarname'] = htmlspecialchars_uni(str_replace(".".$ext, "", $mybb->input['avatar']));
	}

	$form_container = new FormContainer($lang->edit_avatar);
	$form_container->output_row($lang->avatar_name."<em>*</em>", $lang->avatar_name_desc, $form->generate_text_box('avatarname', $mybb->input['avatarname'], array('id' => 'avatarname')), 'avatarname');

	$gallery_list = array();
	$gallery_list[0] = $lang->default_gallery;
	$gallery_dir = scandir(MYBB_ROOT."/".$mybb->settings['avatardir']."/");
	foreach($gallery_dir as $gallery)
	{
		if($gallery != ".." && $gallery != ".")
		{
			if(is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$gallery))
			{
				$gallery_list[$gallery] = htmlspecialchars_uni(str_replace("_", " ", $gallery));
			}
		}
	}

	$form_container->output_row($lang->gallery."<em>*</em>", $lang->gallery_desc, $form->generate_select_box('name', $gallery_list, $mybb->input['name'], array('id' => 'name')), 'name');

	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_avatar);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete_avatar")
{
	$mybb->input['gallery'] = htmlspecialchars_uni($mybb->get_input('gallery'));
	$mybb->input['avatar'] = htmlspecialchars_uni($mybb->get_input('avatar'));
	$ext = get_extension($mybb->input['avatar']);

	if(!is_file(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar']))
	{
		flash_message($lang->error_invalid_avatar, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext))
	{
		flash_message($lang->error_not_avatar, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	if($mybb->request_method == "post")
	{
		@unlink(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar']);

		$avatar = $mybb->settings['avatardir']."/".$mybb->input['gallery']."/".$mybb->input['avatar'];

		$query = $db->simple_select("users", "uid", "avatar LIKE '%{$avatar}%' AND avatartype='gallery'");
		while($user = $db->fetch_array($query))
		{
			$update_avatar = array(
				"avatar" => "",
				"avatardimensions" => "",
				"avatartype" => ""
			);

			$db->update_query("users", $update_avatar, "uid='{$user['uid']}'");
		}

		// Log admin action
		log_admin_action($mybb->input['avatar'], $mybb->input['gallery']);

		if($mybb->input['gallery'])
		{
			$gallery_url = "&amp;gallery=".$mybb->input['gallery'];
		}
		else
		{
			$gallery_url = "";
		}

		flash_message($lang->success_avatar_deleted, 'success');
		admin_redirect("index.php?module=user-avatar_gallery".$gallery_url);
	}
	else
	{
		$page->output_confirm_action("index.php?module=user-avatar_gallery&amp;action=delete_avatar&amp;avatar={$mybb->input['avatar']}", $lang->confirm_avatar_deletion);
	}
}

$mybb->input['gallery'] = $mybb->get_input('gallery');
if($mybb->input['gallery'])
{
	$directory = htmlspecialchars_uni($mybb->get_input('gallery'));
	if(!is_dir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$directory))
	{
		flash_message($lang->error_invalid_gallery, 'error');
		admin_redirect("index.php?module=user-avatar_gallery");
	}

	$page->add_breadcrumb_item($lang->viewing_gallery);
	$page->output_header($lang->avatar_gallery);

	$page->output_nav_tabs($sub_tabs, 'avatar_gallery');

	$table = new Table;
	$table->construct_header($lang->image, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->avatar, array('width' => '55%'));
	$table->construct_header($lang->file_type, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->file_size, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->controls, array('class' => "align_center", 'width' => '15%', 'colspan' => 2));

	$avatar = array();
	$avatars = @opendir(MYBB_ROOT."/".$mybb->settings['avatardir']."/".$directory);
	while($file = @readdir($avatars))
	{
		if($file != ".." && $file != ".")
		{
			$ext = get_extension($file);
			if($ext == "gif" || $ext == "jpg" || $ext == "jpeg" || $ext == "png" || $ext == "bmp")
			{
				$avatar[] = $file;
			}
		}
	}
	@closedir($avatars);

	if(is_array($avatar) && count($avatar) == 0)
	{
		$table->construct_cell($lang->no_avatars_gallery, array("colspan" => "6"));
		$table->construct_row();
	}
	else
	{
		asort($avatar);
		foreach($avatar as $key => $file)
		{
			$image = "../".$mybb->settings['avatardir']."/".$directory."/".$file;
			$ext = get_extension($file);
			$find = str_replace(".".$ext, "", $file);
			$name = htmlspecialchars_uni(ucfirst($find));
			$size = filesize($image);

			if($size < 1024)
			{
				$filesize = $size .' B';
			}
			elseif($size < 1048576)
			{
				$filesize = round($size / 1024, 2) .' KB';
			}
			elseif($size < 1073741824)
			{
				$filesize = round($size / 1048576, 2) . ' MB';
			}

			$table->construct_cell("<img src=\"{$image}\" alt=\"\" />", array("class" => "align_center"));
			$table->construct_cell($name);
			$table->construct_cell($ext, array("class" => "align_center"));
			$table->construct_cell($filesize, array("class" => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=edit_avatar&amp;gallery={$directory}&amp;avatar={$file}\">{$lang->edit}</a>", array("class" => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=delete_avatar&amp;gallery={$directory}&amp;avatar={$file}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_avatar_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
			$table->construct_row();
		}
	}

	$table->output($lang->avatar_gallery);
	$page->output_footer();
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->avatar_gallery);

	$page->output_nav_tabs($sub_tabs, 'avatar_gallery');

	$table = new Table;
	$table->construct_header($lang->image, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->avatar, array('width' => '55%'));
	$table->construct_header($lang->file_type, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->file_size, array("class" => "align_center", 'width' => '10%'));
	$table->construct_header($lang->controls, array('class' => "align_center", 'width' => '15%', 'colspan' => 2));

	$avatar = array();
	$avatars = @opendir(MYBB_ROOT."/".$mybb->settings['avatardir']."/");
	while($file = @readdir($avatars))
	{
		if($file != ".." && $file != ".")
		{
			$avatar[] = $file;
		}
	}
	@closedir($avatars);

	if(is_array($avatar) && count($avatar) == 0)
	{
		$table->construct_cell($lang->no_avatars, array("colspan" => "6"));
		$table->construct_row();
	}
	else
	{
		asort($avatar);
		foreach($avatar as $key => $file)
		{
			$image = "../".$mybb->settings['avatardir']."/".$file;
			$ext = get_extension($file);
			$find = str_replace(".".$ext, "", $file);
			$name = htmlspecialchars_uni(ucfirst($find));
			$size = filesize($image);

			if($size < 1024)
			{
				$filesize = $size .' B';
			}
			elseif($size < 1048576)
			{
				$filesize = round($size / 1024, 2) .' KB';
			}
			elseif($size < 1073741824)
			{
				$filesize = round($size / 1048576, 2) . ' MB';
			}

			if(is_dir($image))
			{
				$cleanname = str_replace("_", " ", $name);
				$table->construct_cell("");
				$table->construct_cell("<strong><a href=\"index.php?module=user-avatar_gallery&amp;gallery={$name}\">{$cleanname}</a></strong>");
				$table->construct_cell("-", array("class" => "align_center"));
				$table->construct_cell("-", array("class" => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=edit_gallery&amp;gallery={$name}\">{$lang->edit}</a>", array("class" => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=delete_gallery&amp;gallery={$name}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_gallery_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
				$table->construct_row();
			}
			else
			{
				$table->construct_cell("<img src=\"{$image}\" alt=\"\" />", array("class" => "align_center"));
				$table->construct_cell($name);
				$table->construct_cell($ext, array("class" => "align_center"));
				$table->construct_cell($filesize, array("class" => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=edit_avatar&amp;avatar={$file}\">{$lang->edit}</a>", array("class" => "align_center"));
				$table->construct_cell("<a href=\"index.php?module=user-avatar_gallery&amp;action=delete_avatar&amp;avatar={$file}&amp;my_post_key={$mybb->post_code}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_avatar_deletion}')\">{$lang->delete}</a>", array("class" => "align_center"));
				$table->construct_row();
			}
		}
	}

	$table->output($lang->avatar_gallery);
	$page->output_footer();
}
