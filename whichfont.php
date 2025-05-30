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

header("Content-type: text/html; charset=UTF-8");
require_once('common.php');
/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<title>Font coverage of translations</title>
<link rel="stylesheet" href="rockbox.css" />
<style type="text/css">

td {
    margin: 0px;
    padding: 0px;
    vertical-align: middle;
}
td img {
    border: 0px solid black;
}
td.lang {
    white-space: nowrap;
}
td.full {
    background-color: green;
}
th {
    margin: 0px;
    padding: 0px;
    vertical-align: bottom;
}

.rotate {
 writing-mode: vertical-lr;
 white-space: nowrap;
 font-weight: normal;
 text-orientation: sideways-right;
 transform: rotate(180deg);
 font-size: 13px;
 min-width: 1.25em;
}

</style>
</head>
<body>

<h1>Language coverage of fonts</h1>

<p>This page lists fonts included with Rockbox and attemps to visualise their
coverage of the included translations. The darker the square, the better
coverage. A <span style="color: green">green</span> square indicates full
coverage.  Hover over the square to see a list of missing glyphs.</p>

<p>Please note that 100% coverage of our translatable strings does not
necessarily mean full coverage for arbitrary text in that (or any other)
language. This is especially true for the CJK language families.</p>

<p>If you wish to contribute a new font, see <a href="https://www.rockbox.org/wiki/CreateFonts">this page</a> on the wiki.</p>

<table>
  <tr>
    <th></th>
<?php

$fontstats = parse_ini_file('scratch/fontcoverage.ini', true);
$langs = languageinfo();
$lang_stats = get_stats();

/* Output the first row - font names */
if (isset($fontstats['english'])) {
    foreach($fontstats['english'] as $font => $coverage) {
        printf("    <th><span class=\"rotate\">%s</span></th>\n", $font);
    }
    print("  </tr>\n");
}

foreach($fontstats as $lang => $stats) {
    if (substr($lang, 0, 7) === 'missing') {
        continue;
    }
    printf("  <tr>\n   <td class='lang' title='%.2f%% complete'><img src='flags/%d/%s.png' /> %s</td>\n", $lang_stats['langstats'][$lang]['percentage'], SMALL_FLAGSIZE, urlencode($langs[$lang]['flag']), $langs[$lang]['name']);
    foreach($stats as $font => $coverage) {
        $hover = "";
        if (isset($fontstats["missing|$lang"]) && isset($fontstats["missing|$lang"][$font])) {
            $hover = $fontstats["missing|$lang"][$font];
        }
        if ($coverage == 1) {
            printf("    <td class='full' title='%s has full coverage of %s'>&nbsp;</td>\n", $font, $lang);
        }
        else {
            $r = 0x9A * (1-$coverage);
            $g = 0xBD * (1-$coverage);
            $b = 0xDE * (1-$coverage);
            printf("    <td style='background-color: #%02X%02X%02X' title='%s has %0.2f%% coverage of %s $hover'>&nbsp;</td>\n", $r, $g, $b, $font, $coverage*100, $lang);
        }
    }
    print("  </tr>\n");
}
?>
</tbody>
</table>
</body>
</html>
