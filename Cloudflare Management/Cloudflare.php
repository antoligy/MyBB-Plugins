<?php
/*
Antoligy MyBB Cloudflare Management Plugin - See README.md

Copyright (c) 2011, Alex "Antoligy" Wilson <antoligy@antoligy.com>

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/

// Config (needs migration to the database)
$Cloudflare = array(
			'API' => 'https://www.cloudflare.com/api_json.html',
			'API_Key' => '<INSERT API KEY HERE>',
			'Email' => '<INSERT@EMAIL.HERE>',
			'Domain' => '<INSERT-DOMAIN.HERE>',
			'Timeout' => 10,
			'Zone' => <INSERT-ZONE-HERE>,
			'Data' => array(),
			);

// Mandidatory Security Check
if(!defined("IN_MYBB")) {
        die("Go away.");
}

$plugins->add_hook("admin_tools_menu", "Cloudflare_menu");
$plugins->add_hook("admin_load", "Cloudflare_admin");
$plugins->add_hook("admin_tools_action_handler", "Cloudflare_action_handler");

if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
  $mybb->ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

function Cloudflare_menu(&$sub_menu) {
    global $mybb;
    end($sub_menu);
    $key = (key($sub_menu))+10;
    if(!$key)
    {
        $key = '160';
    }
    $sub_menu[$key] = array('id' => 'Cloudflare', 'title' => 'Cloudflare Management', 'link' => "index.php?module=tools-Cloudflare");
}

function Cloudflare_action_handler(&$action) {
	$action['Cloudflare'] = array('active' => 'Cloudflare', 'file' => '../../../inc/plugins/Cloudflare.php');
//	$action['Cloudflare'] = array('active' => 'Cloudflare', 'file' => MYBB_ROOT./inc/plugins/Cloudflare.php'); // WHY CAN'T I INCLUDE THIS :\
}

function Cloudflare_admin() {
	global $page, $mybb;
	if($page->active_action != "Cloudflare")
	{
		return;
	}
	$page->add_breadcrumb_item("Cloudflare Management");
}

function Cloudflare_info() {
	return array(
		"name" => "Cloudflare Management",
		"description" => "Basic remote administration for a Cloudflare controlled domain",
		"website" => "http://antoligy.com/",
		"author" => "Alex \"Antoligy\" Wilson",
		"authorsite" => "http://antoligy.com/",
		"version" => "1.0",
		"guid" => "",
		"combatibility" => "*"
	);
}

function unidate($date) {
	return date('H:i:s [d/m/Y]', $date);
}

function getsize($size) {
	$si = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$remainder = $i = 0;
	$size = intval($size*311);
	while ($size >= 1024 && $i < 8) {
		$remainder = (($size & 0x3ff) + $remainder) / 1024;
		$size = $size >> 10;
		$i++;
	}
	return round($size + $remainder, 2) . ' ' . $si[$i];
}

function Cloudflare_POST() {
	global $Cloudflare;
	$c = array();
	$c['c'] = curl_init();
	curl_setopt(&$c['c'], CURLOPT_VERBOSE, 0);
	curl_setopt(&$c['c'], CURLOPT_FORBID_REUSE, true);
	curl_setopt(&$c['c'], CURLOPT_URL, &$Cloudflare['API']);
	curl_setopt(&$c['c'], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt(&$c['c'], CURLOPT_POST, 1);
	curl_setopt(&$c['c'], CURLOPT_POSTFIELDS, $Cloudflare['data']);
	curl_setopt(&$c['c'], CURLOPT_TIMEOUT, &$Cloudflare['Timeout']);
	$c['crs'] = curl_exec(&$c['c']);
	$c['cer'] = curl_error(&$c['c']);
	$c['cht'] = curl_getinfo(&$c['c'], CURLINFO_HTTP_CODE);
	curl_close($c['c']);
	if($c['cht'] != 200) {
		print_r($Cloudflare['data']);
		die('Cloudflare API Error: ' . $c['cer'] . "\n");
	}
	else {
		unset($c);
//		print_r($Cloudflare['data']);
		header('Location: index.php?module=tools-Cloudflare');
	}
}

function Cloudflare_Form() {
	global $Cloudflare;
	switch($_GET['cfaction']) {
		case 'devmode':
			if(isset($_GET['devmode'])) {
				$Cloudflare['data'] = array(
					'a' => 'devmode',
					'z' => &$Cloudflare['Domain'],
					'u' => &$Cloudflare['Email'],
					'tkn' => &$Cloudflare['API_Key'],
					'v' => $_GET['devmode'],
      			);
      		Cloudflare_POST($Cloudflare['data']);
			}
		break;

		case 'secure':
			if(isset($_GET['lvl'])) {
					$Cloudflare['data'] = array(
					'a' => 'sec_lvl',
					'z' => $Cloudflare['Domain'],
					'u' => $Cloudflare['Email'],
					'tkn' => $Cloudflare['API_Key'],
					'v' => $_GET['lvl'],
      			);
      		Cloudflare_POST($Cloudflare['data']);
			}
		break;

		case 'cache':
			if(isset($_GET['cache'])) {
				switch($_GET['cache']) {

					case 'lvl':
						$Cloudflare['data'] = array(
										'a' => 'cache_lvl',
										'z' => $Cloudflare['Domain'],
										'u' => $Cloudflare['Email'],
										'tkn' => $Cloudflare['API_Key'],
										'v' => $_GET['lvl'],
	      		);
         		Cloudflare_POST($Cloudflare['data']);
					break;

					case 'cache':
						$Cloudflare['data'] = array(
										'a' => 'fpurge_ts',
										'z' => $Cloudflare['Domain'],
										'u' => $Cloudflare['Email'],
										'tkn' => $Cloudflare['API_Key'],
										'v' => 1,
	      		);
         		Cloudflare_POST($Cloudflare['data']);
					break;

					case 'snapshot':
						$Cloudflare['data'] = array(
										'a' => 'zone_grab',
										'zid' => $Cloudflare['Zone'],
										'u' => $Cloudflare['Email'],
										'tkn' => $Cloudflare['API_Key'],
	      		);
         		Cloudflare_POST($Cloudflare['data']);
					break;

				}
			}
		break;
	}
}

function Cloudflare_Main() {
	global $Cloudflare;
	$stats = array(
		'a' => 'stats',
		'z' => &$Cloudflare['Domain'],
		'u' => &$Cloudflare['Email'],
		'tkn' => &$Cloudflare['API_Key'],
		);
	$statsc = array();
	$statsc['c'] = curl_init();
	curl_setopt(&$statsc['c'], CURLOPT_VERBOSE, 0);
	curl_setopt(&$statsc['c'], CURLOPT_FORBID_REUSE, true);
	curl_setopt(&$statsc['c'], CURLOPT_URL, &$Cloudflare['API']);
	curl_setopt(&$statsc['c'], CURLOPT_RETURNTRANSFER, 1);
	curl_setopt(&$statsc['c'], CURLOPT_POST, 1);
	curl_setopt(&$statsc['c'], CURLOPT_POSTFIELDS, &$stats);
	curl_setopt(&$statsc['c'], CURLOPT_TIMEOUT, &$Cloudflare['Timeout']);
	$statsc['crs'] = curl_exec(&$statsc['c']);
	$statsc['cer'] = curl_error(&$statsc['c']);
	$statsc['cht'] = curl_getinfo(&$statsc['c'], CURLINFO_HTTP_CODE);
	curl_close($statsc['c']);
	if ($statsc['cht'] != 200) {
		die('Cloudflare API Error: ' . $statsc['cer'] . "\n");
	} else {
		$Cloudflare = json_decode(&$statsc['crs'], true);
	}
	unset($stats);
	unset($statsc);

	$output = array();

//	switch($_GET['action']) {
//		case 'stats':

		// Time
		$output['time'] = new Table;
		$output['time']->construct_header('<b>Type</b>', array("width" => 180));
		$output['time']->construct_header('<b>Value</b>');
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Cloudflare Time:</b>');
		$output['time']->construct_cell(unidate($Cloudflare['response']['result']['objs'][0]['currentServerTime']/1000));
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Server Time (Current):</b>');
		$output['time']->construct_cell(unidate(time()) . ' <i>(' . date('H:i:s',($Cloudflare['response']['result']['objs'][0]['currentServerTime']/1000)-time()) . ' difference)</i>');
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Server Time (Cached):</b>');
		$output['time']->construct_cell(unidate($Cloudflare['response']['result']['objs'][0]['cachedServerTime']/1000));
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Server Cache Expiration:</b>');
		$output['time']->construct_cell(unidate($Cloudflare['response']['result']['objs'][0]['cachedExpryTime']/1000) . ' <i>(' . date('H:i:s',($Cloudflare['response']['result']['objs'][0]['cachedExpryTime']-$Cloudflare['response']['result']['objs'][0]['currentServerTime'])/1000) . ' left)</i>');
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Cloudflare TimeZero:</b>');
		$output['time']->construct_cell(unidate($Cloudflare['response']['result']['timeZero']/1000));
		$output['time']->construct_row();

		$output['time']->construct_cell('<b>Cloudflare TimeEnd:</b>');
		$output['time']->construct_cell(unidate($Cloudflare['response']['result']['timeEnd']/1000));
		$output['time']->construct_row();

		$output['time']->output('<b><u>Time</b></u>');

		// Development Mode
		$output['devmode'] = new Table;
		$output['devmode']->construct_header('<b>Type</b>', array("width" => 180));
		$output['devmode']->construct_header('<b>Value</b>');
		$output['devmode']->construct_row();

		$output['devmode']->construct_cell('<b>Status:</b>');
		if($Cloudflare['response']['result']['objs'][0]['dev_mode'] > $Cloudflare['response']['result']['objs'][0]['currentServerTime']/1000) {
			$output['devmode']->construct_cell('<b>On</b> <i>(off at ' . unidate($Cloudflare['response']['result']['objs'][0]['dev_mode']) . ' - ' . date('H:i:s',$Cloudflare['response']['result']['objs'][0]['dev_mode']-($Cloudflare['response']['result']['objs'][0]['currentServerTime']/1000)) . ' remaining.</i>)');
		} elseif($Cloudflare['response']['result']['objs'][0]['dev_mode']>0) {
			$output['devmode']->construct_cell('Off <i>(since ' . unidate($Cloudflare['response']['result']['objs'][0]['dev_mode']) . ')</i>');
		} else {
			$output['devmode']->construct_cell('Off');
		}
		$output['devmode']->construct_row();

		$output['devmode']->construct_cell('<b>Toggle Mode:</b>');
		$output['devmode']->construct_cell('<form method="get"><input type="hidden" name="module" value="tools-Cloudflare"><input type="hidden" name="cfaction" value="devmode"><select name="devmode"><option value="1">Enable</option><option value="0" selected="selected">Disable</option></select>&nbsp;<input type="submit" value="Apply" /></form>');
		$output['devmode']->construct_row();

		$output['devmode']->output('<b><u>Development Mode</b></u>');

		// Security Settings
		$output['secure'] = new Table;
		$output['secure']->construct_header('<b>Type</b>', array("width" => 180));
		$output['secure']->construct_header('<b>Value</b>');
		$output['secure']->construct_row();

		$output['secure']->construct_cell('<b>Current Security Level:</b>');
		$output['secure']->construct_cell($Cloudflare['response']['result']['objs'][0]['userSecuritySetting']);
		$output['secure']->construct_row();

		$output['secure']->construct_cell('<b>Set Level:</b>');
		$output['secure']->construct_cell('<form method="get"><input type="hidden" name="module" value="tools-Cloudflare"><input type="hidden" name="cfaction" value="secure"><select name="lvl"><option value="high">High</option><option value="med" selected="selected">Medium</option><option value="low">Low</option><option value="eoff">Essentially Off</option></select>&nbsp;<input type="submit" value="Apply" /></form>');
		$output['secure']->construct_row();

		$output['secure']->output('<b><u>Security Settings</b></u>');

		//Caching Level, Cache purge and Snapshot update
		$output['cache'] = new Table;
		$output['cache']->construct_header('<b>Type</b>', array("width" => 180));
		$output['cache']->construct_header('<b>Value</b>');
		$output['cache']->construct_row();

		$output['cache']->construct_cell('<b>Set Cache Level:</b>');
		$output['cache']->construct_cell('<form method="get"><input type="hidden" name="module" value="tools-Cloudflare"><input type="hidden" name="cfaction" value="cache"><select name="lvl"><option value="agg" selected="selected">Aggressive</option><option value="Basic">Basic</option></select>&nbsp;<input type="submit" value="Apply" /></form>');
		$output['cache']->construct_row();

		$output['cache']->construct_cell('<b>Purge Cache:</b>');
		$output['cache']->construct_cell('<form method="get"><input type="hidden" name="module" value="tools-Cloudflare"><input type="hidden" name="cfaction" value="cache"><input type="hidden" name="cache" value="purge"><input type="submit" value="Purge Cache" /></form>');
		$output['cache']->construct_row();

		$output['cache']->construct_cell('<b>Refresh Snapshot:</b>');
		$output['cache']->construct_cell('<form method="get"><input type="hidden" name="module" value="tools-Cloudflare"><input type="hidden" name="cfaction" value="cache"><input type="hidden" name="cache" value="snapshot"><input type="submit" value="Refresh Snapshot" /></form>');
		$output['cache']->construct_row();

		$output['cache']->output('<b><u>Cache Settings</b></u>');

		// Traffic
		$output['traffic'] = new Table;
		$output['traffic']->construct_header('<b>Type</b>', array("width" => 180));
		$output['traffic']->construct_header('<b>Value</b>');
		$output['traffic']->construct_row();

		$output['traffic']->construct_cell('<b>Pageviews (regular):</b>');
		$output['traffic']->construct_cell($Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['regular']);
		$output['traffic']->construct_row();

		$output['traffic']->construct_cell('<b>Pageviews (crawler):</b>');
		$output['traffic']->construct_cell($Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['crawler']);
		$output['traffic']->construct_row();

		$output['traffic']->construct_cell('<b>Pageviews (threat):</b>');
		$output['traffic']->construct_cell($Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['threat']);
		$output['traffic']->construct_row();

		$output['traffic']->construct_cell('<b>Pageviews (total):</b>');
		$output['traffic']->construct_cell($Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['regular']+$Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['crawler']+$Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['pageviews']['threat']);
		$output['traffic']->construct_row();

		$output['traffic']->construct_cell('<b>Unique Visitors:</b>');
		$output['traffic']->construct_cell($Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['uniques']['regular']+$Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['uniques']['crawler']+$Cloudflare['response']['result']['objs'][0]['trafficBreakdown']['uniques']['threat']);
		$output['traffic']->construct_row();

		$output['traffic']->output('<b><u>Traffic</b></u>');

		// Bandwidth
		$output['bandwidth'] = new Table;
		$output['bandwidth']->construct_header('<b>Type</b>', array("width" => 180));
		$output['bandwidth']->construct_header('<b>Value</b>');
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Requests served by Webserver:</b>');
		$output['bandwidth']->construct_cell($Cloudflare['response']['result']['objs'][0]['requestsServed']['user']);
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Requests served by Cloudflare:</b>');
		$output['bandwidth']->construct_cell($Cloudflare['response']['result']['objs'][0]['requestsServed']['cloudflare']);
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Total Requests Served:</b>');
		$output['bandwidth']->construct_cell($Cloudflare['response']['result']['objs'][0]['requestsServed']['user']+$Cloudflare['response']['result']['objs'][0]['requestsServed']['cloudflare']);
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Bandwidth Served By Webserver:</b>');
		$output['bandwidth']->construct_cell(getsize($Cloudflare['response']['result']['objs'][0]['bandwidthServed']['user']));
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Bandwidth Served By Cloudflare:</b>');
		$output['bandwidth']->construct_cell(getsize($Cloudflare['response']['result']['objs'][0]['bandwidthServed']['cloudflare']));
		$output['bandwidth']->construct_row();

		$output['bandwidth']->construct_cell('<b>Total Bandwidth Served:</b>');
		$output['bandwidth']->construct_cell(getsize(bcadd($Cloudflare['response']['result']['objs'][0]['bandwidthServed']['cloudflare'], $Cloudflare['response']['result']['objs'][0]['bandwidthServed']['user'], 6)));
		$output['bandwidth']->construct_row();

		$output['bandwidth']->output('<b><u>Bandwidth</b></u>');

//var_dump($Cloudflare);
//		break;
//	}
}
if($mybb->input['module'] == 'tools-Cloudflare') {
	if(!is_super_admin($mybb->user['uid'])) {
		flash_message($lang->cannot_perform_action_super_admin_general, 'error');
		} else {
		if(!$mybb->input['action']) {
			$page->add_breadcrumb_item('Cloudflare Management', 'index.php?module=tools-Cloudflare');
			$page->output_header();
			print('<img src="http://www.cloudflare.com/images/layout/cloudflare-logo.png"><br />'."\n");
			if(isset($_GET['cfaction'])) {
				Cloudflare_Form($Cloudflare);
//				Cloudflare_POST($Cloudflare);
			}
			else {
				Cloudflare_Main($Cloudflare);
			}
//		var_dump($Cloudflare);

//		print_r($data);
//		print('test');
//		print_r($GLOBALS['data']);
			unset($Cloudflare);
			$page->output_footer();
		}
	}
}
?>
