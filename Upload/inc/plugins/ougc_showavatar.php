<?php

/***************************************************************************
 *
 *	OUGC Show Avatar plugin (/inc/plugins/ougc_showavatar.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 - 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Show last poster's avatar in forum index and forum display pages.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Run the required hooks.
if(defined('IN_ADMINCP'))
{
	// We need to refresh the announcements cache when required.
	$plugins->add_hook('admin_forum_announcements_add_commit', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('admin_forum_announcements_edit_commit', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('admin_forum_announcements_delete_commit', 'ougc_showavatar_update_announcements');

	$plugins->add_hook('admin_config_settings_start', 'ougc_showavatar_langload');
	$plugins->add_hook('admin_style_templates_set', 'ougc_showavatar_langload');
	$plugins->add_hook('admin_config_settings_change', 'ougc_showavatar_settings_change');
}
else
{
	$plugins->add_hook('build_forumbits_forum', 'ougc_showavatar_forumbits', 0);
	$plugins->add_hook('forumdisplay_announcement', 'ougc_showavatar_announcement');
	$plugins->add_hook('forumdisplay_thread', 'ougc_showavatar_forumdisplay');
	$plugins->add_hook('search_results_thread', 'ougc_showavatar_search_thread');
	$plugins->add_hook('search_results_post', 'ougc_showavatar_search_post');
	$plugins->add_hook('portal_end', 'ougc_showavatar_portal');
	$plugins->add_hook('private_end', 'ougc_showavatar_pms');

	// We need to refresh the announcements cache when required.
	$plugins->add_hook('modcp_do_new_announcement_end', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('modcp_do_edit_announcement_end', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('modcp_do_delete_announcement', 'ougc_showavatar_update_announcements');

	// Cache our template.
	if(in_array(THIS_SCRIPT, array('index.php', 'forumdisplay.php', 'search.php', 'portal.php', 'private.php')))
	{
		global $templatelist;

		if(isset($templatelist))
		{
			$templatelist .= ',';
		}
		else
		{
			$templatelist = '';
		}

		$templatelist .= 'ougcshowavatar';
	}
}

// Plugin API
function ougc_showavatar_info()
{
	global $lang;
	ougc_showavatar_langload();

	return array(
		'name'          => 'OUGC Show Avatar',
		'description'   => $lang->setting_group_ougc_showavatar_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '2.1',
		'versioncode'	=> 2100,
		'compatibility'	=> '18*',
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
		)
	);
}

// _activate() routine
function ougc_showavatar_activate()
{
	global $PL, $lang, $cache;
	ougc_showavatar_plappend();

	ougc_showavatar_update_announcements();

	// Add settings
	$PL->settings('ougc_showavatar', $lang->setting_group_ougc_showavatar, $lang->setting_group_ougc_showavatar_desc, array(
		'index'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_index,
		   'description'	=> $lang->setting_ougc_showavatar_index_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'forum'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_forum,
		   'description'	=> $lang->setting_ougc_showavatar_forum_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'search'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_search,
		   'description'	=> $lang->setting_ougc_showavatar_search_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'disforums'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_disforums,
		   'description'	=> $lang->setting_ougc_showavatar_disforums_desc,
		   'optionscode'	=> 'forumselect',
		   'value'			=> ''
		),
		'portal'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_portal,
		   'description'	=> $lang->setting_ougc_showavatar_portal_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'private'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_private,
		   'description'	=> $lang->setting_ougc_showavatar_private_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'doticons'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_doticons,
		   'description'	=> $lang->setting_ougc_showavatar_doticons_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'maxwh'	=> array(
		   'title'			=> $lang->setting_ougc_showavatar_maxwh,
		   'description'	=> $lang->setting_ougc_showavatar_maxwh_desc,
		   'optionscode'	=> 'text',
		   'value'			=> '40x40'
		)
	));

	// Add templates
	$PL->templates('ougcshowavatar', $lang->ougc_showavatar, array(
		''	=> '<img src="{$user[\'avatar\']}" alt="{$user[\'username\']}" title="{$user[\'username\']}" width="{$user[\'width\']}" height="{$user[\'height\']}" />'
	));

	// Insert our variables.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('forumbit_depth2_forum_lastpost', '#'.preg_quote('<span class="smalltext">').'#', '{$forum[\'avatar\']}<span class="smalltext">');
	find_replace_templatesets('forumdisplay_announcements_announcement', '#'.preg_quote('<td class="{$bgcolor}">').'#', '<td class="{$bgcolor}">{$announcement[\'avatar\']}');
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$attachment_count}').'#', '{$thread[\'avatar\']}{$attachment_count}');
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('<span class="lastpost smalltext">').'#', '{$thread[\'lastpostavatar\']}<span class="lastpost smalltext">');
	find_replace_templatesets('search_results_threads_thread', '#'.preg_quote('{$attachment_count}').'#', '{$thread[\'avatar\']}{$attachment_count}');
	find_replace_templatesets('search_results_threads_thread', '#'.preg_quote('<span class="smalltext">').'#', '{$thread[\'lastpostavatar\']}<span class="smalltext">');
	find_replace_templatesets('search_results_posts_post', '#'.preg_quote('{$lang->post_thread}').'#', '{$post[\'avatar\']}{$lang->post_thread}');
	find_replace_templatesets('portal_latestthreads_thread', '#'.preg_quote('<td class="{$altbg}">').'#', '<td class="{$altbg}"><!--OUGC_SHOWAVATAR[{$thread[\'uid\']}]-->');
	find_replace_templatesets('portal_latestthreads_thread', '#'.preg_quote('<a href="{$thread[\'lastpostlink').'#', '<!--OUGC_SHOWAVATAR[{$thread[\'lastposteruid\']}]--><a href="{$thread[');
	find_replace_templatesets('private_messagebit', '#'.preg_quote('<td class="trow1" width="35%">').'#', '<td class="trow1" width="35%"><!--OUGC_SHOWAVATAR[{$message[\'fromid\']}]-->');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_showavatar_info();

	if(!isset($plugins['showavatar']))
	{
		$plugins['showavatar'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['showavatar'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_showavatar_deactivate()
{
	// Remove our variables.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('forumbit_depth2_forum_lastpost', '#'.preg_quote('{$forum[\'avatar\']}').'#', '',0);
	find_replace_templatesets('forumdisplay_announcements_announcement', '#'.preg_quote('{$announcement[\'avatar\']}').'#', '&nbsp;');
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$thread[\'avatar\']}').'#', '', 0);
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$thread[\'lastpostavatar\']}').'#', '', 0);
	find_replace_templatesets('search_results_threads_thread', '#'.preg_quote('{$thread[\'avatar\']}').'#', '', 0);
	find_replace_templatesets('search_results_threads_thread', '#'.preg_quote('{$thread[\'lastpostavatar\']}').'#', '', 0);
	find_replace_templatesets('search_results_posts_post', '#'.preg_quote('{$post[\'avatar\']}').'#', '', 0);
	find_replace_templatesets('portal_latestthreads_thread', '#'.preg_quote('<!--OUGC_SHOWAVATAR[{$thread[\'uid\']}]-->').'#', '', 0);
	find_replace_templatesets('portal_latestthreads_thread', '#'.preg_quote('<!--OUGC_SHOWAVATAR[{$thread[\'lastposteruid\']}]-->').'#', '', 0);
	find_replace_templatesets('private_messagebit', '#'.preg_quote('<!--OUGC_SHOWAVATAR[{$message[\'fromid\']}]-->').'#', '', 0);
}

// _is_installed() routine
function ougc_showavatar_is_installed()
{
	global $cache;

	$plugins = (array)$cache->read('ougc_plugins');

	return !empty($plugins['showavatar']);
}

// _uninstall() routine
function ougc_showavatar_uninstall()
{
	global $PL, $cache;
	ougc_showavatar_plappend();

	// Delete settings
	$PL->settings_delete('ougc_showavatar');

	// Delete template/group
	$PL->templates_delete('ougcshowavatar');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['showavatar']))
	{
		unset($plugins['showavatar']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Load language file
function ougc_showavatar_langload()
{
	global $lang;

	isset($lang->ougc_showavatar_name) or $lang->load('ougc_showavatar');
}

// Pretty settings
function ougc_showavatar_settings_change()
{
	global $db, $mybb;

	$query = $db->simple_select('settinggroups', 'name', "gid='{$mybb->get_input('gid', 1)}'");

	($db->fetch_field($query, 'name') != 'ougc_showavatar') or ougc_showavatar_langload();
}

// Cache announcements data
function ougc_showavatar_update_announcements()
{
	global $cache, $db;

	$query = $db->query('
		SELECT a.uid, u.username, u.avatar, u.avatardimensions
		FROM '.TABLE_PREFIX.'announcements a
		LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=a.uid)
		WHERE enddate>'.TIME_NOW.' OR enddate=\'0\'
	');

	$announcements = array();
	while($announcement = $db->fetch_array($query))
	{
		$announcements[$announcement['uid']] = $announcement;
	}

	$cache->update('ougc_showavatar', $announcements);
}

// PluginLibrary requirement check
function ougc_showavatar_plappend()
{
	ougc_showavatar_langload();

	if(!file_exists(PLUGINLIBRARY))
	{
		global $lang;
		$info = ougc_showavatar_info();

		flash_message($lang->sprintf($lang->ougc_showavatar_pl_req, $info['pl']['ulr'], $info['pl']['version']), 'error');
		admin_redirect('index.php?module=config-plugins');
	}


	global $PL;
	$PL or require_once PLUGINLIBRARY;

	if($PL->version < $info['pl']['version'])
	{
		global $lang;
		$info = ougc_showavatar_info();

		flash_message($lang->sprintf($lang->ougc_showavatar_pl_old, $PL->version, $info['pl']['version'], $info['pl']['ulr']), 'error');
		admin_redirect('index.php?module=config-plugins');
	}
}

// Show the avatar in forum list.
function ougc_showavatar_forumbits(&$f)
{
	$f['avatar'] = '';

	static $userscache = null;
	if($userscache === null)
	{
		global $settings, $templates, $forum_cache;
		$forum_cache or cache_forums();

		if(!$settings['ougc_showavatar_index'] || my_strpos($templates->cache['forumbit_depth2_forum_lastpost'], '{$forum[\'avatar\']}') === false || !$forum_cache)
		{
			global $plugins;

			$plugins->remove_hook('build_forumbits_forum', 'ougc_showavatar_forumbits');
			return;
		}

		global $db;

		$query = $db->query('
			SELECT u.uid, u.username, u.avatar, u.avatardimensions, f.fid, f.lastpost
			FROM '.TABLE_PREFIX.'forums f
			LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=f.lastposteruid)
			WHERE f.active=\'1\' AND f.type=\'f\'
			ORDER BY f.lastpost DESC
		');

		// Build the cache
		$userscache = array();
		while($user = $db->fetch_array($query))
		{
			$userscache[$user['fid']] = $user;
			unset($userscache[$user['fid']]['fid']);
		}

		foreach($userscache as $fid => &$user)
		{
			$forum = $forum_cache[$fid];
			if($forum['pid'].','.$forum['fid'] != $forum['parentlist'])
			{
				$parent_time = $userscache[$forum['pid']]['lastpost'];
				if($userscache[$forum['fid']]['lastpost'] >= $parent_time)
				{
					$userscache[$forum['pid']] = $userscache[$forum['fid']];
				}
			}
		}
	}

	if(isset($userscache[$f['fid']]))
	{
		unset($userscache[$f['fid']]['lastpost']);
		$f['avatar'] = ougc_showavatar_get($userscache[$f['fid']]);
	}
	else
	{
		$f['avatar'] = ougc_showavatar_get();
	}
}

// Show the avatar in forum announcements list.
function ougc_showavatar_announcement()
{
	global $settings, $announcement;

	$announcement['avatar'] = '';

	static $userscache = null;

	if($userscache === null)
	{
		global $templates, $cache;

		if(!$settings['ougc_showavatar_forum'] || $settings['ougc_showavatar_disforums'] == -1 || my_strpos(','.$settings['ougc_showavatar_disforums'].',', ','.$announcement['fid'].',') !== false || my_strpos($templates->cache['forumdisplay_announcements_announcement'], '{$announcement[\'avatar\']}') === false)
		{
			global $plugins;

			$plugins->remove_hook('build_forumbits_forum', 'ougc_showavatar_forumbits');
			return;
		}

		$userscache = $cache->read('ougc_showavatar');
	}

	if(isset($userscache[$announcement['uid']]))
	{
		$announcement['avatar'] = ougc_showavatar_get($userscache[$announcement['uid']]);
	}
	else
	{
		$announcement['avatar'] = ougc_showavatar_get();
	}
}

// Show the avatar in forum threads list.
function ougc_showavatar_forumdisplay()
{
	global $settings, $thread, $tids;
	$thread['avatar'] = $thread['lastpostavatar'] = '';

	static $userscache = null;

	if($userscache === null)
	{
		global $templates;
		$userscache = array();

		if(!$settings['ougc_showavatar_forum'] || $settings['ougc_showavatar_disforums'] == -1 || my_strpos(','.$settings['ougc_showavatar_disforums'].',', ','.$thread['fid'].',') !== false || (my_strpos($templates->cache['forumdisplay_thread'], '{$thread[\'avatar\']}') === false && my_strpos($templates->cache['forumdisplay_thread'], '{$thread[\'lastpostavatar\']}') === false) || !$tids)
		{
			global $plugins;

			$plugins->remove_hook('forumdisplay_thread', 'ougc_showavatar_forumdisplay');
			return;
		}

		global $db;

		$query = $db->query('
			SELECT u.uid, u.username, u.avatar, u.avatardimensions, lu.uid, lu.username, lu.avatar, lu.avatardimensions
			FROM '.TABLE_PREFIX.'threads t
			LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=t.uid)
			LEFT JOIN '.TABLE_PREFIX.'users lu ON (lu.uid=t.lastposteruid)
			WHERE t.tid IN ('.implode(',', array_unique(array_map('intval', explode(',', $tids)))).')
		');
		while($user = $db->fetch_array($query))
		{
			$userscache[$user['uid']] = $user;
		}
	}

	if(isset($userscache[$thread['uid']]))
	{
		$thread['avatar'] = ougc_showavatar_get($userscache[$thread['uid']]);
	}
	else
	{
		$thread['avatar'] = ougc_showavatar_get();
	}

	if(isset($userscache[$thread['lastposteruid']]))
	{
		$thread['lastpostavatar'] = ougc_showavatar_get($userscache[$thread['lastposteruid']]);
	}
	else
	{
		$thread['lastpostavatar'] = ougc_showavatar_get();
	}
}

// Show avatars in search results | threads
function ougc_showavatar_search_thread()
{
	global $settings, $thread;
	$thread['avatar'] = $thread['lastpostavatar'] = '';

	static $userscache = null;

	if($userscache === null)
	{
		global $templates, $search;
		$userscache = array();

		$tids = implode(',', array_unique(array_map('intval', explode(',', $search['threads']))));

		if(!$settings['ougc_showavatar_search'] || (my_strpos($templates->cache['search_results_threads_thread'], '{$thread[\'avatar\']}') === false && my_strpos($templates->cache['search_results_threads_thread'], '{$thread[\'lastpostavatar\']}') === false && my_strpos($templates->cache['search_results_posts_post'], '{$post[\'avatar\']}') === false) || !$tids || $search['resulttype'] != 'threads')
		{
			global $plugins;

			$plugins->remove_hook('search_results_thread', 'ougc_showavatar_search_thread');
			return;
		}

		global $db;

		$query = $db->query('
			SELECT u.uid, u.username, u.avatar, u.avatardimensions, lu.uid, lu.username, lu.avatar, lu.avatardimensions
			FROM '.TABLE_PREFIX.'threads t
			LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=t.uid)
			LEFT JOIN '.TABLE_PREFIX.'users lu ON (lu.uid=t.lastposteruid)
			WHERE t.tid IN ('.$tids.')
		');
		while($user = $db->fetch_array($query))
		{
			$userscache[$user['uid']] = $user;
		}
	}

	if(isset($userscache[$thread['uid']]))
	{
		$thread['avatar'] = ougc_showavatar_get($userscache[$thread['uid']]);
	}
	else
	{
		$thread['avatar'] = ougc_showavatar_get();
	}

	if(isset($userscache[$thread['lastposteruid']]))
	{
		$thread['lastpostavatar'] = ougc_showavatar_get($userscache[$thread['lastposteruid']]);
	}
	else
	{
		$thread['lastpostavatar'] = ougc_showavatar_get();
	}
}

// Show avatars in search results | posts
function ougc_showavatar_search_post()
{
	global $settings, $post;
	$post['avatar'] = '';

	static $userscache = null;

	if($userscache === null)
	{
		global $templates, $search;
		$userscache = array();

		$pids = implode(',', array_unique(array_map('intval', explode(',', $search['posts']))));

		if(!$settings['ougc_showavatar_search'] || my_strpos($templates->cache['search_results_posts_post'], '{$post[\'avatar\']}') === false || !$pids || $search['resulttype'] != 'posts')
		{
			global $plugins;

			$plugins->remove_hook('search_results_post', 'ougc_showavatar_search_post');
			return;
		}

		global $db;

		$query = $db->query('
			SELECT u.uid, u.username, u.avatar, u.avatardimensions
			FROM '.TABLE_PREFIX.'posts p
			LEFT JOIN '.TABLE_PREFIX.'users u ON (u.uid=p.uid)
			WHERE p.pid IN ('.$pids.')
		');
		while($user = $db->fetch_array($query))
		{
			$userscache[$user['uid']] = $user;
		}
	}

	if(isset($userscache[$post['uid']]))
	{
		$post['avatar'] = ougc_showavatar_get($userscache[$post['uid']]);
	}
	else
	{
		$post['avatar'] = ougc_showavatar_get();
	}
}

// Portal avatars
function ougc_showavatar_portal()
{
	global $settings, $templates, $latestthreads;

	if(!$settings['ougc_showavatar_portal'] || (my_strpos($templates->cache['portal_latestthreads_thread'], '<!--OUGC_SHOWAVATAR[{$thread[\'uid\']}]-->') === false && my_strpos($templates->cache['portal_latestthreads_thread'], '<!--OUGC_SHOWAVATAR[{$thread[\'lastposteruid\']}]-->') === false) || !$latestthreads)
	{
		return;
	}

	preg_match_all('#\<\!--OUGC_SHOWAVATAR\[([0-9]+)\]--\>#i', $latestthreads, $matches);

	$uids = array_unique(array_map('intval', $matches[1]));
	unset($matches);

	if(!$uids)
	{
		return;
	}

	global $db;

	$query = $db->simple_select('users', 'uid, username, avatar, avatardimensions', 'uid IN (\''.implode('\',\'', $uids).'\')');

	$replace = array();
	while($user = $db->fetch_array($query))
	{
		$replace['<!--OUGC_SHOWAVATAR['.(int)$user['uid'].']-->'] = ougc_showavatar_get($user);
	}

	if($replace)
	{
		$latestthreads = str_replace(array_keys($replace), array_values($replace), $latestthreads);
	}

	$latestthreads = preg_replace('#\<\!--OUGC_SHOWAVATAR\[([0-9]+)\]--\>#i', ougc_showavatar_get(), $latestthreads);
}

// Private messages
function ougc_showavatar_pms()
{
	global $settings, $templates, $messagelist;

	if(!$settings['ougc_showavatar_private'] || my_strpos($templates->cache['private_messagebit'], '<!--OUGC_SHOWAVATAR[{$message[\'fromid\']}]-->') === false || !$messagelist)
	{
		return;
	}

	preg_match_all('#\<\!--OUGC_SHOWAVATAR\[([0-9]+)\]--\>#i', $messagelist, $matches);

	$uids = array_unique(array_map('intval', $matches[1]));
	unset($matches);

	if(!$uids)
	{
		return;
	}

	global $db;

	$query = $db->simple_select('users', 'uid, username, avatar, avatardimensions', 'uid IN (\''.implode('\',\'', $uids).'\')');

	$replace = array();
	while($user = $db->fetch_array($query))
	{
		$replace['<!--OUGC_SHOWAVATAR['.(int)$user['uid'].']-->'] = ougc_showavatar_get($user);
	}

	if($replace)
	{
		$messagelist = str_replace(array_keys($replace), array_values($replace), $messagelist);
	}

	$messagelist = preg_replace('#\<\!--OUGC_SHOWAVATAR\[([0-9]+)\]--\>#i', ougc_showavatar_get(), $messagelist);
}

// Save us time getting the data
function ougc_showavatar_get($user=array('uid' => 0))
{
	static $cache = array();

	if(!isset($cache[$user['uid']]))
	{
		global $settings, $templates;

		$user['uid'] = (int)$user['uid'];

		if(empty($user['username']))
		{
			global $lang;

			$user['username'] = $lang->guest;
		}
		$user['username'] = htmlspecialchars_uni($user['username']);
		$user['profilelink'] = get_profile_link($user['uid']);

		if(empty($user['avatar']))
		{
			$user['avatar'] = $settings['useravatar'];
			$user['avatardimensions'] = $settings['useravatardims'];
		}

		$user['avatar'] = htmlspecialchars_uni($user['avatar']);
		$dimensions = explode('|', $user['avatardimensions']);

		if(isset($dimensions[0]) && isset($dimensions[1]))
		{
			list($maxwidth, $maxheight) = explode('x', my_strtolower($settings['ougc_showavatar_maxwh']));
			if($dimensions[0] > (int)$maxwidth || $dimensions[1] > (int)$maxheight)
			{
				require_once MYBB_ROOT.'inc/functions_image.php';
				$scale = scale_image($dimensions[0], $dimensions[1], (int)$maxwidth, (int)$maxheight);
			}
			$user['width'] = (int)(isset($scale['width']) ? $scale['width'] : $dimensions[0]);
			$user['height'] = (int)(isset($scale['height']) ? $scale['height'] : $dimensions[1]);
		}

		eval('$cache[$user[\'uid\']] = "'.$templates->get('ougcshowavatar').'";');
	}

	return $cache[$user['uid']];
}