#!/usr/bin/php -q
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
chdir(dirname(__FILE__));

function my_exec($cmd) {
    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("pipe", "w"),
    );
    $p = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($p)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $retval = proc_close($p);
        return array($retval, $stdout, $stderr);
    }
    else {
        return false;
    }
}

function update_langs() {
/*
    chmod('rockbox/apps/lang', 0777); // Make sure the web server can write temp files

    // This is no longer needed as we have a cron job doing the git repo update
    $cmds = <<<END
cd rockbox && /usr/bin/git checkout -f
cd rockbox && /usr/bin/git pull
END;
    foreach(explode("\n", $cmds) as $cmd) {
        print("$ ".$cmd."\n");
        list($retval, $stdout, $stderr) = my_exec($cmd);
        if ($retval == 0) { print($stdout); }
        else { printf("retval: %d\nSTDOUT:\n%s\nSTDERR:\n%s\n--------------------------------------------------\n", $retval, $stdout, $stderr); }
    }
*/
    $fp = fopen(VERSIONS, 'w');
    foreach(glob('rockbox/apps/lang/*.lang') as $lang) {
        $gitstr = shell_exec(sprintf("cd rockbox && git log --pretty=%%H -1 %s",
                "apps/lang/" . basename($lang)));
        $line = sprintf("%s:%s\n", basename($lang, '.lang'), trim($gitstr));
        fwrite($fp, $line);
    }
    fclose($fp);
    return true;
}

function genstats() {
    $langs = array();
    foreach(file(VERSIONS) as $line) {
        list($lang, $version) = explode(":", trim($line));
        $langs[$lang] = $version;
    }

    $stats = array();
    foreach($langs as $lang => $rev) {
        $cmd = sprintf("%s -s rockbox/tools/updatelang rockbox/apps/lang/english.lang rockbox/apps/lang/%s.lang -", PERL, $lang);
        $output = shell_exec($cmd);
//        print("$ $cmd\n");
//        printf("%s\n", $output);
        file_put_contents(sprintf("scratch/%s.lang.update", $lang), $output);
        list($lastrev, $lastupdate) = getlastupdated($lang);
            $stat = array('name' => $lang, 'total' => 0, 'missing' => 0, 'desc' => 0, 'source' => 0, 'dest' => 0, 'destdup' => 0, 'voice' => 0, 'voicedup' => 0, 'last_update' => $lastupdate, 'last_update_rev' => $lastrev);
        foreach(explode("\n", $output) as $line) {
            if (preg_match('/### This phrase below was not present/', $line) || // DELETEME
                preg_match('/### This phrase is missing entirely/', $line)) {
                    $stat['missing']++;
            } elseif (preg_match('/### The <source> section differs/', $line) || // DELETEME
                      preg_match("/### The <source> section for '.*' is missing/", $line) ||
                      preg_match("/### The <source> section for '.*' differs/", $line)) {
                    $stat['source']++;
            } elseif (preg_match("/### The 'desc' field differs/", $line) || // DELETEME
                      preg_match("/### The 'desc' field for '.*' differs/", $line) ||
                      preg_match("/### The 'user' field for '.*' differs/", $line)) {
                    $stat['desc']++;
            } elseif (preg_match("/### The <dest> section for '.*' is missing/", $line) ||
                      preg_match("/### The <dest> section for '.*' is blank/", $line)) {
                    $stat['dest']++;
            } elseif (preg_match("/### The <dest> section for '.*' is identica/", $line)) {
		    $stat['destdup']++;
            } elseif (preg_match("/### The <voice> section for '.*' is missing/", $line) ||
                      preg_match("/### The <voice> section for '.*' is blank/", $line)) {
                    $stat['voice']++;
            } elseif (preg_match("/### The <voice> section for '.*' is identical/", $line)) {
		    $stat['voicedup']++;
            } elseif (preg_match('/<phrase>/', $line)) {
                    $stat['total']++;
            }
        }
        $stats[$lang] = $stat;
    }
    file_put_contents(STATS, serialize($stats));
    return true;
}

function getlastupdated($lang) {
    $retries = 0;
    while ($retries < 5) {
        try {
            $gitstr = shell_exec(sprintf("cd rockbox && git log --pretty=%%H,%%ct -50 apps/lang/%s.lang", $lang));
            $line = sprintf("%s:%s\n", basename($lang, '.lang'), $gitstr);
            $retries = 100;
        }
        catch (Exception $e) {
            $retries++;
            printf("Warning: Caught exception: %s (trying again)<br />\n", $e->getMessage());
            if ($retries > 5) die("Cannot succeed :(");
        }
    }
    $ignorehash = explode("\n", file_get_contents('ignoredhash.list'));
    foreach(preg_split('(\n\r|\r\n|\r|\n)', $gitstr) as $logentry) {
        list($rev, $date) = explode(",", trim($logentry));
        if(!in_array($rev, $ignorehash)) {
            return array($rev, $date);
        }
    }
    return array(0, 0);
}

function update_flags() {
    $languageinfo = languageinfo();
    $path = "e/e6";

    if (!file_exists('flags')) {
        mkdir('flags');
    }
    foreach (array(SMALL_FLAGSIZE, LARGE_FLAGSIZE) as $size) {
        if (!file_exists('flags/'.$size)) {
            mkdir('flags/'.$size);
        }
        foreach($languageinfo as $lang) {
            $dest = sprintf("flags/%d/%s.png", $size, $lang['flag']);
            if (!file_exists($dest)) {
                $src = sprintf('http://upload.wikimedia.org/wikipedia/commons/thumb/%3$s/Flag_of_%2$s.svg/%1$dpx-Flag_of_%2$s.svg.png', $size, $lang['flag'], $path);
                printf("%s \n --> %s\n", $src, $dest);
                copy($src, $dest);
                sleep(1.0); # Don't be rude
            }
        }
    }
}

update_langs();
genstats();
update_flags();
?>
