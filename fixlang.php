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
header("Content-type: text/plain; charset=UTF-8");
require_once('common.php');
mb_internal_encoding("UTF-8");

$langs = glob('rockbox/apps/lang/*.lang');
$langname = isset($_GET['lang']) ? $_GET['lang'] : 'HUGHAGHGULUAAUL';
$cmds = explode(",", isset($_GET['cmd']) ? $_GET['cmd'] : '');
foreach(array('voice', 'sort', 'empty') as $cmd) {
    if (isset($_GET[$cmd]) && $_GET[$cmd] == 'on') {
        $cmds[] = $cmd;
    }
}
function my_basename(&$i) { $i = basename($i, '.lang'); }
array_walk($langs, 'my_basename');

if (in_array($langname, $langs)) {
    if (isset($_GET['sendfile']))
        header(sprintf("Content-Disposition: attachment; filename=%s-fix-%s.diff", $langname, str_replace('/', '_', implode(',', $cmds))));
    $langfile = sprintf("rockbox/apps/lang/%s.lang", $langname);
    $tempname = sprintf('rockbox/apps/lang/%s.lang-fixlang-%s', $langname, uniqid('',$_SERVER['REMOTE_ADDR'].$langname));
    $lang = parselangfile($langfile);
    $english = parselangfile("rockbox/apps/lang/english.lang");

    // Copy voice over if English source and voice are the same
    if (in_array('voice', $cmds)) {
        foreach($lang as $id => $phrase) {
            if ($english[$id]['source'] == $english[$id]['voice']) {
                $lang[$id]['voice'] = $lang[$id]['dest'];
            }
        }
    }

    // Mirror empty/none strings from English to target
    if (in_array('empty', $cmds)) {
        foreach($lang as $id => $phrase) {
            foreach(array('dest', 'voice') as $what) {
                foreach($phrase[$what] as $target => $value) {
                    if (!isset($english[$id][$what][$target]))
                        unset($lang[$id][$what][$target]);
                    $e = $english[$id][$what][$target];
                    if ($e == 'none' || $e == 'deprecated' || $e == "") {
                        $lang[$id][$what][$target] = $e;
                    }
                }
            }
        }
    }

    // Sort language in English order
    if (in_array('sort', $cmds)) {
        function langsort($a, $b) {
            static $english_ids = true;
            if ($english_ids === true) {
                $english_ids = array_keys(parselangfile("rockbox/apps/lang/english.lang"));
            }
            if ($a === $b) return 0;
            return (array_search($a, $english_ids) < array_search($b, $english_ids) ? -1 : 1);
        }
        uksort($lang, 'langsort');
    }

    $fp = fopen($tempname, 'w');
    // Print header
    foreach(file($langfile) as $line) {
        if (substr($line, 0, 1) != '#') break;
        fwrite($fp, $line);
    }
    // Print all phrases
    foreach($lang as $id => $phrase) {
        fwrite($fp, printphrase($phrase));
    }
    fclose($fp);

echo <<<MOO
Copyright by individual Rockbox contributors
See
https://git.rockbox.org/cgit/rockbox.git/log/apps/lang/$langname.lang
for details.
May be distributed under the terms of the GNU GPL version 2 or later

MOO;

    printf("This diff was generated using http://%s%s\n\n", $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    print("The following actions were taken:\n");
    print(" * Stricter syntax used (uniform whitespace, user field in all phrases)\n");
    foreach($cmds as $cmd) {
        switch($cmd) {
            case 'voice': print(" * Copying dest to voice for phrases where dest and voice are the same\n   in english.lang\n"); break;
            case 'empty': print(" * Make empty and 'none' strings match english.lang\n"); break;
            case 'sort':  print(" * Sorting phrases in the order found in english.lang\n"); break;
        }
    }
    print(shell_exec(sprintf("/usr/bin/diff -u rockbox/apps/lang/%s.lang %s", $langname, $tempname)));
    unlink($tempname);
}

?>
