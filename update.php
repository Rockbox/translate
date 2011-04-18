#!/usr/bin/php5 -q
<?php
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
    chmod('rockbox/apps/lang', 0777); // Make sure the web server can write temp files
    $cmds = <<<END
/usr/bin/svn cleanup rockbox/apps/lang
/usr/bin/svn update  rockbox/apps/lang
/usr/bin/svn cleanup rockbox/tools/
/usr/bin/svn update  rockbox/tools/genlang
/usr/bin/svn update  rockbox/fonts
END;
    foreach(explode("\n", $cmds) as $cmd) {
        print("$ ".$cmd."\n");
        list($retval, $stdout, $stderr) = my_exec($cmd);
        if ($retval == 0) { print($stdout); }
        else { printf("retval: %d\nSTDOUT:\n%s\nSTDERR:\n%s\n--------------------------------------------------\n", $retval, $stdout, $stderr); }
    }

    $fp = fopen(VERSIONS, 'w');
    foreach(glob('rockbox/apps/lang/*.lang') as $lang) {
        $xmlstr = shell_exec(sprintf("svn info --xml %s", $lang));
        list($retval, $xml, $stderr) = new SimpleXMLElement($xmlstr);
        $line = sprintf("%s:%d\n", basename($lang, '.lang'), $xml->entry->commit->attributes()->revision[0]);
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
        $cmd = sprintf("%s -s rockbox/tools/genlang -u -e=rockbox/apps/lang/english.lang rockbox/apps/lang/%s.lang", PERL, $lang);
        $output = shell_exec($cmd);
        print("$ $cmd\n");
        #printf("%s\n", $output);
        file_put_contents(sprintf("rockbox/apps/lang/%s.lang.update", $lang), $output);
        list($lastrev, $lastupdate) = getlastupdated($lang);
            $stat = array('name' => $lang, 'total' => 0, 'missing' => 0, 'desc' => 0, 'source' => 0, 'last_update' => $lastupdate, 'last_update_rev' => $lastrev);
        foreach(explode("\n", $output) as $line) {
            switch(trim($line)) {
                case '### This phrase below was not present in the translated file':
                    $stat['missing']++;
                    break;
                case '### The <source> section differs from the english!':
                    $stat['source']++;
                    break;
                case '### The \'desc\' field differs from the english!':
                    $stat['desc']++;
                    break;
                case '<phrase>':
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
            $xmlstr = shell_exec(sprintf("svn log --xml rockbox/apps/lang/%s.lang", $lang));
            $xml = new SimpleXMLElement($xmlstr);
            $retries = 100;
        }
        catch (Exception $e) {
            $retries++;
            printf("Warning: Caught exception: %s (trying again)<br />\n", $e->getMessage());
            if ($retries > 5) die("Cannot succeed :(");
        }
    }
    $ignorerevs = explode("\n", file_get_contents('ignoredrevs.list'));
    foreach($xml->logentry as $logentry) {
        $rev = (string) $logentry['revision'];
        preg_match('@([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})\.[0-9]*(.*)@', $logentry->date, $matches);
        $date = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
        switch($matches[7]) {
            case 'Z':
                $date += date("Z", $date);
                break;
        }
        if (!in_array($rev, $ignorerevs)) {
            return array($rev, $date);
        }
    }
    return array(0, 0);
}

function update_flags() {
    $languageinfo = languageinfo();

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
                $src = sprintf('http://upload.wikimedia.org/wikipedia/commons/thumb/c/c3/Flag_of_%2$s.svg/%1$dpx-Flag_of_%2$s.svg.png', $size, $lang['flag']);
                printf("%s \n --> %s\n", $src, $dest);
                copy($src, $dest);
                sleep(1.5); # Don't be rude
            }
        }
    }
}

update_langs();
genstats();
update_flags();
?>
