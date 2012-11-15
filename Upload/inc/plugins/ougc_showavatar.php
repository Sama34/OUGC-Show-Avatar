<?php

/***************************************************************************
 *
 *   OUGC Show Avatar plugin (/inc/plugins/ougc_showavatar.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Show last poster's avatar in forum index and forum display pages.
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
	// We need to refresh the annoucements cache when required.
	$plugins->add_hook('admin_forum_announcements_add_commit', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('admin_forum_announcements_edit_commit', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('admin_forum_announcements_delete_commit', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('admin_config_settings_change_commit', 'ougc_showavatar_update_settings');
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

	// We need to refresh the annoucements cache when required.
	$plugins->add_hook('modcp_do_new_announcement_end', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('modcp_do_edit_announcement_end', 'ougc_showavatar_update_announcements');
	$plugins->add_hook('modcp_do_delete_announcement', 'ougc_showavatar_update_announcements');

	// Cache our template.
	if(in_array(THIS_SCRIPT, array('index.php', 'forumdisplay.php', 'search', 'portal', 'private')))
	{
		global $templatelist;
		if(isset($templatelist))
		{
			$templatelist .= ', ougcshowavatar';
		}
	}
}

// This plugin information.
function ougc_showavatar_info()
{
	global $lang;
	ougc_showavatar_langload();

	return array(
		'name'          => 'OUGC Show Avatar',
		'description'   => $lang->ougc_showavatar_d,
		'website'		=> 'http://mods.mybb.com/view/ougc-avatar-in-forum-list',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '2.0',
		'compatibility'	=> '16*',
		'guid'          => '429db5af61674651c32f32e97ffd1168',
		'pl_url'		=> 'http://mods.mybb.com/view/pluginlibrary',
		'pl_version'	=> 11
	);
}

// Load language files when necessary
function ougc_showavatar_langload()
{
	global $lang;

	isset($lang->ougc_showavatar_name) or $lang->load('ougc_showavatar');
}

// This plugin activate information.
function ougc_showavatar_activate()
{
	static $done = false;

	if(!$done)
	{
		global $PL, $lang;
		ougc_showavatar_plappend();
		ougc_showavatar_langload();
		ougc_showavatar_update_announcements();

		// Add settings
		$PL->settings('ougc_showavatar', $lang->ougc_showavatar, $lang->ougc_showavatar_d, array(
			'index'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_index,
			   'optionscode'	=> 'yesno'
			),
			'forum'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_forum,
			   'optionscode'	=> 'yesno'
			),
			'search'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_search,
			   'optionscode'	=> 'yesno'
			),
			'disforums'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_disforums,
			   'optionscode'	=> 'text'
			),
			'portal'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_portal,
			   'optionscode'	=> 'yesno'
			),
			'private'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_private,
			   'optionscode'	=> 'yesno'
			),
			/*'doticons'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_doticons,
			   'optionscode'	=> 'yesno'
			),*/
			'maxwh'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_maxwh,
			   'optionscode'	=> 'text',
			   'value'			=> '40x40'
			),
			'defaultava'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_defaultava,
			   'optionscode'	=> 'text',
			   'value'			=> $GLOBALS['mybb']->settings['bburl'].'/images/avatars/invalid_url.gif'
			),
			'defaultavawh'	=> array(
			   'title'			=> $lang->ougc_showavatar_s_defaultavawh,
			   'optionscode'	=> 'text',
			   'value'			=> '85|85'
			),
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
		find_replace_templatesets('portal_latestthreads_thread', '#'.preg_quote('<a href="{$thread[').'#', '<!--OUGC_SHOWAVATAR[{$thread[\'lastposteruid\']}]--><a href="{$thread[');
		find_replace_templatesets('private_messagebit', '#'.preg_quote('<td class="trow1" width="35%">').'#', '<td class="trow1" width="35%"><!--OUGC_SHOWAVATAR[{$message[\'fromid\']}]-->');
	}

	$done = true;
}

// This plugin deactivate information.
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

// _is_installed
function ougc_showavatar_is_installed()
{
	global $settings;

	return isset($settings['ougc_showavatar_index']);
}

// _install
function ougc_showavatar_install()
{
	ougc_showavatar_activate();
}

// _uninstall
function ougc_showavatar_uninstall()
{
	global $PL;
	ougc_showavatar_plappend();

	// Delete settings
	$PL->settings_delete('ougc_showavatar');

	// Delete template/group
	$PL->templates_delete('ougcshowavatar');
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

	if($announcements)
	{
		$cache->update('ougc_showavatar', $announcements);
	}
}

// PluginLibrary requirement check
function ougc_showavatar_plappend()
{
	if(!file_exists(PLUGINLIBRARY))
	{
		global $lang;
		$info = ougc_showavatar_info();

		flash_message($lang->sprintf($lang->ougc_showavatar_pl_req, $info['pl_url'], $info['pl_version']), 'error');
		admin_redirect('index.php?module=config-plugins');
	}


	global $PL;
	$PL or require_once PLUGINLIBRARY;

	if($PL->version < $info['pl_version'])
	{
		global $lang;
		$info = ougc_showavatar_info();

		flash_message($lang->sprintf($lang->ougc_showavatar_pl_old, $PL->version, $info['pl_version'], $info['pl_url']), 'error');
		admin_redirect('index.php?module=config-plugins');
	}
}

// Make sure only desired setting values are being saved up to the DB
function ougc_showavatar_update_settings()
{
	global $mybb;

	$settings = array();

	if(isset($mybb->input['upsetting']['ougc_showavatar_disforums']))
	{
		$do = array_flip(array_unique(array_map('intval', explode(',', $mybb->input['upsetting']['ougc_showavatar_disforums']))));
		unset($do[0]);

		if($do)
		{
			$settings['ougc_showavatar_disforums'] = implode(',', array_keys($do));
		}
	}

	if(isset($mybb->input['upsetting']['ougc_showavatar_maxwh']))
	{
		$do = explode('x', my_strtolower($mybb->input['upsetting']['ougc_showavatar_maxwh']));

		if(!isset($do[0]) || !isset($do[1]))
		{
			$do[0] = $do[1] = 40;
		}
		else
		{
			$do = array_map('intval', $do);
		}

		$settings['ougc_showavatar_maxwh'] = $do[0].'x'.$do[1];
	}

	if(isset($mybb->input['upsetting']['ougc_showavatar_defaultavawh']))
	{
		$do = explode('|', $mybb->input['upsetting']['ougc_showavatar_defaultavawh']);

		if(!isset($do[0]) || !isset($do[1]))
		{
			$do[0] = $do[1] = 80;
		}
		else
		{
			$do = array_map('intval', $do);
		}

		$settings['ougc_showavatar_defaultavawh'] = $do[0].'|'.$do[1];
	}

	if(isset($mybb->input['upsetting']['ougc_showavatar_defaultava']))
	{
		$url = trim($mybb->input['upsetting']['ougc_showavatar_defaultava']);

		if(!$url || (!my_strpos($url, 'http://') && !my_strpos($url, 'https://')))
		{
			$url = $mybb->settings['bburl'].'/images/avatars/invalid_url.gif';
		}
		$settings['ougc_showavatar_defaultava'] = $url;
	}

	global $db;

	foreach($settings as $name => $value)
	{
		$db->update_query('settings', array('value' => $db->escape_string($value)), 'name=\''.$db->escape_string($name).'\'');
	}
}

// Show the avatar in forum list.
function ougc_showavatar_forumbits(&$f)
{
	$f['avatar'] = '';

	static $userscache = null;
	if($userscache === null)
	{
		global $settings;

		if(!(bool)$settings['ougc_showavatar_index'])
		{
			global $plugins;

			$plugins->remove_hook('build_forumbits_forum', 'ougc_showavatar_forumbits');
			return;
		}

		global $templates;

		if(my_strpos($templates->cache['forumbit_depth2_forum_lastpost'], '{$forum[\'avatar\']}') === false)
		{
			global $plugins;

			$plugins->remove_hook('build_forumbits_forum', 'ougc_showavatar_forumbits');
			return;
		}

		global $forum_cache;
		$forum_cache or cache_forums();

		if(!$forum_cache)
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
		$userscache = $cache->read('ougc_showavatar');

		if(!(bool)$settings['ougc_showavatar_forum'] || ($settings['ougc_showavatar_disforums'] !== '' && $announcement['fid'] && $announcement['fid'] != '-1' && in_array($announcement['fid'], array_unique(array_map('intval', explode(',', $settings['ougc_showavatar_disforums']))))) || (my_strpos($templates->cache['forumdisplay_announcements_announcement'], '{$announcement[\'avatar\']}') === false))
		{
			global $plugins;

			$plugins->remove_hook('build_forumbits_forum', 'ougc_showavatar_forumbits');
			return;
		}
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

		if(!$tids || !(bool)$settings['ougc_showavatar_forum'] || ($settings['ougc_showavatar_disforums'] !== '' && in_array($thread['fid'], array_unique(array_map('intval', explode(',', $settings['ougc_showavatar_disforums']))))) || (my_strpos($templates->cache['forumdisplay_thread'], '{$thread[\'avatar\']}') === false && my_strpos($templates->cache['forumdisplay_thread'], '{$thread[\'lastpostavatar\']}') === false))
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

		if(!$tids || $search['resulttype'] != 'threads' || !(bool)$settings['ougc_showavatar_search'] || (my_strpos($templates->cache['search_results_threads_thread'], '{$thread[\'avatar\']}') === false && my_strpos($templates->cache['search_results_threads_thread'], '{$thread[\'lastpostavatar\']}') === false && my_strpos($templates->cache['search_results_posts_post'], '{$post[\'avatar\']}') === false))
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

		if(!$pids || $search['resulttype'] != 'posts' || !(bool)$settings['ougc_showavatar_search'] || my_strpos($templates->cache['search_results_posts_post'], '{$post[\'avatar\']}') === false)
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

	if(!(bool)$settings['ougc_showavatar_portal'] || empty($latestthreads) || (my_strpos($templates->cache['portal_latestthreads_thread'], '<!--OUGC_SHOWAVATAR[{$thread[\'uid\']}]-->') === false && my_strpos($templates->cache['portal_latestthreads_thread'], '<!--OUGC_SHOWAVATAR[{$thread[\'lastposteruid\']}]-->') === false))
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

	if(!(bool)$settings['ougc_showavatar_private'] || empty($messagelist) || my_strpos($templates->cache['private_messagebit'], '<!--OUGC_SHOWAVATAR[{$message[\'fromid\']}]-->') === false)
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

			$user['avatar'] = $settings['ougc_showavatar_defaultava'];
			$user['avatardimensions'] = $settings['ougc_showavatar_defaultavawh'];
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