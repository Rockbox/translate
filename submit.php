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

function submit() {
    $type = $_POST['full'] ? 'lang' : 'diff';

    header("Content-type: text/plain;charset=UTF-8");
    header(sprintf("Content-Disposition: attachment; filename=%s.%s", $_REQUEST['lang'], $type));

    $langs = array();
    if (file_exists(VERSIONS)) {
        foreach(file(VERSIONS) as $line) {
            list($lang, $version) = explode(":", trim($line));
            $langs[$lang] = $version;
        }
    }

    $i = 0;
    do {
        $filename = sprintf("/tmp/%s.lang%s.new", $_REQUEST['lang'], $i == 0 ? '' : '.'.$i);
        $i++;
    } while (file_exists($filename));

    $fp = fopen($filename, 'w');
    if ($fp === false) {
	header("HTTP/1.1 500 Internal Server Error");
	print "\nUnable to write tmpfile\n";
	exit(1);
    }

    // Write a header if one exists
    $original_lines = file(sprintf("rockbox/apps/lang/%s.lang", $_REQUEST['lang']));
    foreach($original_lines as $i => $line) {
        if (substr($line, 0, 1) == "<") { break; }
        fwrite($fp, $line);
    }

    $original = parselangfile(sprintf("scratch/%s.lang.update", $_REQUEST['lang']));
    $english = parselangfile("scratch/english.lang");

    if (! $_POST['full']) {
        print("Copyright by individual Rockbox contributors\n");
        printf("See\nhttps://git.rockbox.org/cgit/rockbox.git/log/apps/lang/%s.lang\nfor details.\n", $_REQUEST['lang']);
        print("May be distributed under the terms of the GNU GPL version 2 or later\n");
        print("This file generated by http://translate.rockbox.org/\n\n");
        printf("This translation was based on git hash %s of the original.\n\n", $langs[$_REQUEST['lang']]);
    }

    foreach($original as $id => $phrase) {
        foreach(array('dest', 'voice') as $type) {
            if (isset($_POST['phrases'][$id])) {
                if (isset($_POST['phrases'][$id][$type]))
                    $phrase[$type] = $_POST['phrases'][$id][$type];
                else
                    unset($phrase[$type]);
            }
        }
        if (strtolower($english[$id]['phrase']['desc']) == "deprecated") {
            $phrase = $english[$id];
        }
        fwrite($fp, printphrase($phrase));
    }
    fclose($fp);

    if ($_POST['full']) {
        $cmd = sprintf("/usr/bin/cat %s", $filename);
    } else {
        $cmd = sprintf("/usr/bin/diff -u -B -w rockbox/apps/lang/%s.lang %s", escapeshellarg($_REQUEST['lang']), $filename);
    }
    print(shell_exec($cmd));
}

submit();
?>
