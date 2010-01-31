<?php
define("PERL", '/usr/bin/perl');
define("STATS", 'stats.dat');
define("VERSIONS", 'versions.dat');
define("SMALL_FLAGSIZE", '22');
define("LARGE_FLAGSIZE", '150');

require_once('templater.class.php');
$smarty = new templater('/usr/share/php/smarty/libs');
$smarty->assign('languages', languageinfo());
$smarty->assign('updated', file_exists(STATS) ? filemtime(STATS) : 0);

function languageinfo() {
    return parse_ini_file('languages.ini', true);
}

function parselangfile($filename) {
    $lines = @file($filename);
    if (!is_array($lines)) {
        return false;
    }
    $phrases = array();
    $empty = array(
        'source' => array(),
        'dest' => array(),
        'voice' => array(),
        'notes' => array(),
    );
    $thisphrase = $empty;
    
    $pos = 'phrase';
    
    foreach($lines as $lineno => $line) {
        $line = trim($line);
        
        if (preg_match("/^### (.*)$/", $line, $matches)) {
            if (strpos($matches[1], "The phrase is not used. Skipped") === false) {
                $thisphrase['notes'][] = $matches[1];
            }
        }
        elseif ($pos == 'phrase' && preg_match("/^([^:]+): ?(.*)$/", $line, $matches)) {
            $thisphrase[$pos][$matches[1]] = $matches[2];
        }
        elseif ($pos != 'phrase' && preg_match("/^([^:]+): ?\"?([^\"]*)\"?$/", $line, $matches)) {
            $thisphrase[$pos][$matches[1]] = $matches[2];
        }

        switch($line) {
            case '</voice>':
            case '</dest>':
            case '</source>':
            case '<phrase>': $pos = 'phrase'; break;
            case '</phrase>':
                $phrases[$thisphrase['phrase']['id']] = $thisphrase;
                $thisphrase = $empty;
                $pos = 'lang';
                break;
            case '<source>': $pos = 'source'; break;
            case '<dest>': $pos = 'dest'; break;
            case '<voice>': $pos = 'voice'; break;
        }
    }
    return $phrases;    
}

function printphrase($phrase) {
    $ret = '';
    $ret .= sprintf("<phrase>\n  id: %s\n  desc:%s\n  user:%s\n",
        $phrase['phrase']['id'],
        isset($phrase['phrase']['desc']) && $phrase['phrase']['desc'] != "" ? ' '.$phrase['phrase']['desc'] : '',
        isset($phrase['phrase']['user']) && $phrase['phrase']['user'] != "" ? ' '.$phrase['phrase']['user'] : ''
    );
    
    foreach(array('source', 'dest', 'voice') as $field) {
        $ret .= sprintf("  <%s>\n", $field);
        if (isset($phrase[$field])) {
            /* If '*' is empty, we don't catch it on the edit-page */
            if (!isset($phrase[$field]['*'])) {
                $ret .= "    *: \"\"\n";
            }
            foreach($phrase[$field] as $target => $string) {
                if ($target == 'desc' || $target == 'user' || $target == 'id') continue;
                if (trim($string) == 'none') { $string = 'none'; }
                elseif (strtolower(trim($string)) == 'deprecated') { $string = 'deprecated'; }
                $format = ($string == 'none' || $string == 'deprecated' ? "    %s: %s\n" : "    %s: \"%s\"\n");
                    $ret .= sprintf($format, $target, $string);
            }
        }
        else {
            $ret .= "    *: \"\"\n";
        }
        $ret .= sprintf("  </%s>\n", $field);
    }
    $ret .= "</phrase>\n";
    return $ret;
}

function print_head() {
header("Content-type: text/html; charset=UTF-8");
echo <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Rockbox Translations</title>
<link rel="stylesheet" href="rockbox.css" />
</head>
<body>
END;
}

function print_foot() {
$date = date('D M j H:i:s T Y', file_exists(STATS) ? filemtime(STATS) : 0);
echo <<<END
<hr />
<a href="http://www.rockbox.org">
  <img src="http://www.rockbox.org/rockbox100.png" border="0" width="100" height="32" alt="www.rockbox.org" title="Rockbox - Open Source Jukebox Firmware" />
</a>
<small>
Last updated $date. Flags copyright Wikimedia contributors.
</small>

</body>
</html>
END;
}
?>
