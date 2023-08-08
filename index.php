<?php
/************************************************************************
 *             __________               __   ___.
 *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
 *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
 *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
 *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
 *                     \/            \/     \/    \/            \/
 * Copyright (C) 2010 Jonas Häggqvist
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This software is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY
 * KIND, either express or implied.
 *
 **************************************************************************/
error_reporting(E_ALL);
require_once('common.php');

    function langsort($a, $b) {
	// English is always on top
	if ($a['name'] == 'English')
		return -1;
	if ($b['name'] == 'English')
		return 1;

        // Sort by Name
        $an = $a['name'];
        $bn = $b['name'];
        // Sort by last update revision
        $au = $a['last_update_rev'];
        $bu = $b['last_update_rev'];
        // Sort by status
        $ap = $a['percentage'];
        $bp = $b['percentage'];

        $ac = $a['desc'] + $a['source'];
        $bc = $b['desc'] + $b['source'];
        if ($ap == $bp) {
            if ($ac != $bc) {
                return $ac > $bc ? -1 : 1;
            } else {
                return $an > $bn ? -1 : 1;
            }
        }
        return $ap < $bp ? 1 : -1;
    }

function get_stats() {
    $languageinfo = languageinfo();
    $stats['langstats'] = (file_exists(STATS) ? unserialize(file_get_contents(STATS)) : array());
    $stats['summary'] = array('complete' => -1, 'good' => 0, 'normal' => 0, 'bad' => 0);
    foreach($stats['langstats'] as $name => &$info) {
        if ($name == 'summary') continue;
        $info['percentage'] = ($info['total'] - $info['missing']) / $info['total'] * 100;
        if (isset($languageinfo[$name])) {
            $info = array_merge($info, $languageinfo[$name]);
        }
        // Set some defaults
        else {
            $info = array_merge($info, array(
                'name' => ucfirst($info['name']),
                'flag' => 'unknown',
                'code' => 'xx',
                'rtl' => 0,
            ));
        }

        /* Count this language into the summary */
        switch(true) {
            case $info['percentage'] == 100
                   && $info['source'] == 0
                   && $info['desc'] == 0:
                $stats['summary']['complete']++;
                break;
            case $info['percentage'] > 90;
                $stats['summary']['good']++;
                break;
            case $info['percentage'] > 50;
                $stats['summary']['normal']++;
                break;
            default:
                $stats['summary']['bad']++;
                break;
        }
    }
    uasort($stats['langstats'], 'langsort');
    return $stats;
}


$stats = get_stats();
$smarty->render('frontpage.tpl', $stats);
?>
