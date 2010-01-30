<?php
error_reporting(E_ALL); 
require_once('common.php');

function submit() {
    header("Content-type: text/plain;charset=UTF-8");
    header(sprintf("Content-Disposition: attachment; filename=%s.diff", $_REQUEST['lang']));

    $i = 0;
    do {
        $filename = sprintf("apps/lang/%s.lang%s.new", $_REQUEST['lang'], $i == 0 ? '' : '.'.$i);
        $i++;
    } while (file_exists($filename));

    $fp = fopen($filename, 'w');
    $langs = array();
    if (file_exists(VERSIONS)) {
        foreach(file(VERSIONS) as $line) {
            list($lang, $version) = explode(":", trim($line)); 
            $langs[$lang] = $version;
        }
    }
    
    // Write a header if one exists
    $original_lines = file(sprintf("apps/lang/%s.lang", $_REQUEST['lang']));
    foreach($original_lines as $i => $line) {
        if (substr($line, 0, 1) == "<") { break; }
        fwrite($fp, $line);
    }
    
    $original = parselangfile(sprintf("apps/lang/%s.lang.update", $_REQUEST['lang']));
    $english = parselangfile("apps/lang/english.lang");
    print("Copyright by individual Rockbox contributors\n");
    printf("See\nhttp://svn.rockbox.org/viewvc.cgi/trunk/apps/lang/%s.lang?view=log\nfor details.\n", $_REQUEST['lang']);
    print("May be distributed under the terms of the GNU GPL version 2 or later\n");
    print("This file generated by http://translate.rockbox.org/\n\n");
    printf("This translation was based on SVN revision %d of the original.\n\n", $langs[$_REQUEST['lang']]);

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
    $cmd = sprintf("/usr/bin/diff -u -B -w apps/lang/%s.lang %s", escapeshellarg($_REQUEST['lang']), $filename);
    print(shell_exec($cmd));
}

submit();
?>
