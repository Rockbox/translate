<?php
error_reporting(E_ALL); 
require_once('common.php');

function qs($count) {
    return $count == 1 ? '' : 's';
}

function simpleduration($seconds) {
    $one_hour = 60*60;
    $one_day = 24*$one_hour;
    if ($seconds > 60*28 && $seconds < 60*32) {
        return "half an hour ago";
    }
    elseif ($seconds < $one_hour) {
        $minutes = floor($seconds/60);
        return sprintf("%d minute%s ago", $minutes, qs($minutes));
    }
    elseif ($seconds < $one_day) {
        $hours = floor($seconds/$one_hour);
        return sprintf("%d hour%s ago", $hours, qs($hours));
    }
    elseif ($seconds < 2*$one_day) {
        return "Yesterday";
    }
    elseif ($seconds < 7*$one_day) {
        $days = floor($seconds/$one_day);
        return sprintf("%d day%s ago", $days, qs($days));
    }
    elseif ($seconds < 2*30*$one_day) {
        $weeks = floor($seconds/(7*$one_day));
        return sprintf("%d week%s ago", $weeks, qs($weeks));
    }
    elseif ($seconds < 365*$one_day) {
        $months = floor($seconds/(30*$one_day));
        return sprintf("%d month%s ago", $months, qs($months));
    }
    else {
        $years = floor($seconds/(365*$one_day));
        return sprintf("%d year%s ago", $years, qs($years));
    }
}

function show_stats() {
    $languageinfo = languageinfo();

    $stats = (file_exists(STATS) ? unserialize(file_get_contents(STATS)) : array());
    ksort($stats);
    //*
    function langsort($a, $b) {
        // Sort by Name
        $ap = $a['name'];
        $bp = $b['name'];
        // Sort by last update revision
        $ap = $a['Last update rev'];
        $bp = $b['Last update rev'];
        // Sort by status
        $ap = ($a['Total strings'] - $a['Missing strings']) / $a['Total strings']; 
        $bp = ($b['Total strings'] - $b['Missing strings']) / $b['Total strings'];
        
        $ac = $a['Changed desc'] + $a['Changed source'];
        $bc = $b['Changed desc'] + $b['Changed source'];
        if ($ap == $bp) {
            if ($ac != $bc) {
                return $ac < $bc ? -1 : 1;
            }
            else {
                return $a['name'] < $b['name'] ? -1 : 1;
            }
        }
        return $ap < $bp ? 1 : -1; 
    }
    uasort($stats, 'langsort');
    //*/
    
    $langs = array();
    $versions = (file_exists(VERSIONS) ? file(VERSIONS) : array());
    foreach($versions as $line) {
        list($lang, $version) = explode(":", trim($line)); 
        $langs[$lang] = $version;
    }
  
    echo <<<END
<h1>Rockbox translations</h1>
<p>Go <a href="../">back</a>.</p>

<p>
On this page you can see the current status of the various Rockbox translations.
Ideally, all languages below should be at 100%. In reality though, this is not
the case. You can help remedy this situation by clicking on the name of a
language you speak and help translate Rockbox all within the comfort of your
webbrowser. Alternatively, you can go to <a
href="http://www.rockbox.org/twiki/bin/view/Main/LangFiles">this page</a> in the
Rockbox wiki, which will tell you how to update languages the more manual, but
possibly slightly safer way.
</p>

<h2>Current translation status</h2>
<!--

END;
    $complete = 0;
    $good = 0;
    $normal = 0;
    $bad = 0;
    foreach($stats as $lang => $stat) {
        $percentage = ($stat['Total strings'] - $stat['Missing strings']) / $stat['Total strings'] * 100;
        $lang = isset($languageinfo[$lang]['name']) ? strip_tags($languageinfo[$lang]['name']) : ucfirst(strtolower($lang));
        switch(true) {
            case $percentage == 100 && $stat['Changed source'] == 0 && $stat['Changed desc'] == 0:
                printf("COMPLETE: %3d%% - %s\n", $percentage, $lang);
                $complete++;
                break;
            case $percentage > 95;
                printf("GOOD:     %3d%% - %s\n", $percentage, $lang);
                $good++;
                break;
            case $percentage > 50;
                printf("NORMAL:   %3d%% - %s\n", $percentage, $lang);
                $normal++;
                break;                
            default:
                printf("BAD:      %3d%% - %s\n", $percentage, $lang);
                $bad++;
                break;
        }
    }
    printf("-->\n<p>\n\t<ul>\n\t\t<li>%d complete translations</li>\n\t\t<li>%d good translations (&gt;95%% translated)</li>\n\t\t<li>%d decent translations (&gt;50%% translated)</li>\n\t\t<li>%d bad translations (&lt;50%% translated)</li>\n\t</ul>\n</p>\n\n", $complete, $good, $normal, $bad);
    echo <<<END
<table>
    <thead>
    <tr>
        <td colspan='4'>Language</td>
        <td>Last update</td>
        <td>Progress</td>
        <td>Missing strings</td>
        <td>Changed description</td>
        <td>Changed source</td>
    </tr>
    </thead>
END;
    foreach($stats as $lang => $stat) {
        $percentage = ($stat['Total strings'] - $stat['Missing strings']) / $stat['Total strings'] * 100;
        $img = isset($languageinfo[$lang]['flag']) && $languageinfo[$lang]['flag'] != '' && file_exists('flags/'.SMALL_FLAGSIZE.'/'.$languageinfo[$lang]['flag'].'.png') ? sprintf('<img class="flagthumb" src="flags/'.SMALL_FLAGSIZE.'/%s.png" %s />', $languageinfo[$lang]['flag'], array_pop(array_slice(getimagesize('flags/'.SMALL_FLAGSIZE.'/'.$languageinfo[$lang]['flag'].'.png'), 3, 1))) : '';
        if ($stat['Missing strings'] == 0 && $stat['Changed desc'] == 0 && $stat['Changed source'] == 0) {
            $format = "<tr class='good'><td>%s</td><td><a href='problems.php?lang=%s'><img style='border: none' src='warning.gif' width='16' height='16' /></td><td><a href='graphs/%s.png'><img style='border: none' src='graph.png' width='16' height='16' /></td><td><a href='edit.php?lang=%s'></a>%s</td><td><a href='http://svn.rockbox.org/viewvc.cgi?view=rev&revision=%d' title='%s'>%s</a></td><td><img title='%.2f%%' src='graph.php?p=%.2f'></td><td align='right'>%d</td><td align='right'>%d</td><td align='right'>%d</td></tr>\n";
        }
        elseif ($percentage < 50) {
            $format = "<tr class='poor'><td>%s</td><td><a href='problems.php?lang=%s'><img style='border: none' src='warning.gif' width='16' height='16' /></td><td><a href='graphs/%s.png'><img style='border: none' src='graph.png' width='16' height='16' /></td><td><a href='edit.php?lang=%s'>%s</a></td><td><a href='http://svn.rockbox.org/viewvc.cgi?view=rev&revision=%d' title='%s'>%s</a></td><td><img title='%.2f%%' src='graph.php?p=%.2f'></td><td align='right'>%d</td><td align='right'>%d</td><td align='right'>%d</td></tr>\n";
        }
        else {
            $format = "<tr><td>%s</td><td><a href='problems.php?lang=%s'><img style='border: none' src='warning.gif' width='16' height='16' /></td><td><a href='graphs/%s.png'><img style='border: none' src='graph.png' width='16' height='16' /></td><td><a href='edit.php?lang=%s'>%s</a></td><td><a href='http://svn.rockbox.org/viewvc.cgi?view=rev&revision=%d' title='%s'>%s</a></td><td><img title='%.2f%%' src='graph.php?p=%.2f'></td><td align='right'>%d</td><td align='right'>%d</td><td align='right'>%d</td></tr>\n";
        } 
        printf($format,
            $img,
            $lang, $lang, $lang,
            isset($languageinfo[$lang]['name']) ? $languageinfo[$lang]['name'] : ucfirst(strtolower($lang)),
            $stat['Last update rev'],
            date(DATE_RFC2822, $stat['Last update']),
            simpleduration(time() - $stat['Last update']),
            $percentage,
            $percentage,
            $stat['Missing strings'],
            $stat['Changed desc'],
            $stat['Changed source']
        );
    }
    print("</table>");


     echo <<<END
<h2>Perform automated cleanup</h2>
<p>Using the form below, it's possible to perform automated cleanups of a
translation. Be aware though, that this might produce unwanted results in some
cases, so you're required to check the results rather than blindly trusting
them.</p>

<form action="fixlang.php" method="GET">
<table>
    <tr>
        <td>Language</td>
        <td>
            <select name="lang">
END;
    ksort($stats);
    foreach($stats as $lang => $stat) {
        printf("                <option value='%s'>%s</option>\n", $lang, $languageinfo[$lang]['name']);
    }
echo <<<END
            </select>
        </td>
    </tr>
END;
    $foo = array(
        array(
            'id' => "voice",
            'name' => "Copy voice strings",
            'title' => "Copy translation to voice for phrases where string and voice are the same in the English language file"
        ),
        array(
            'id' => "empty",
            'name' => "Fix empty strings",
            'title' => "Make empty and \"none\" strings match the English language file"
        ),
        array(
            'id' => "sort",
            'name' => "Sort in English order",
            'title' => "Sort phrases in the same order as the English language file"
        ),
        array(
            'id' => "sendfile",
            'name' => "Save result as file",
            'title' => "Prompt to save the result on disk"
        )
    );
    foreach($foo as $row) {
        // ;
        printf("<tr><td><label for='%s' title='%s'>%s</label></td><td><input type='checkbox' id='%s', name='%s' title='%s' /></td></tr>\n",
            $row['id'],
            $row['title'],
            $row['name'],
            $row['id'],
            $row['id'],
            $row['title']
        );
    }
echo <<<END
    <tr>
        <td align="right" colspan="2"><input type="submit" /></td>
    </tr>
</table>
</form>
END;
}

print_head();
show_stats();
print_foot();
?>
