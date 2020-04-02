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
</style>
</head>
<body>

<h1>Language coverage of fonts</h1>

<p>This page lists fonts included with Rockbox and tries to visualise their
coverage of the included translations. The darker the square, the better
coverage. A <span style="color: green">green</span> square indicates full
coverage.</p>

<table>
  <thead>
  <tr>
    <td></td>
<?php

function getverticalimg($text) {
    $filename = sprintf('headers/%s.png', str_replace("/", "_", $text));
    if (!file_exists($filename) || filemtime(__FILE__) > filemtime($filename)) {
        $height = 200;
        $width = 11;
        $im = imagecreate($width, $height);
        $bg = imagecolorallocate($im, 0x9A, 0xBD, 0xDE);
        $fg = imagecolorallocate($im, 0, 0, 0);
        imagestringup($im, 2, -1, $height - 2, $text, $fg);
        imagecolortransparent($im, $bg);
        imagepng($im, $filename);
    }
    return sprintf("<img src='%s' />", $filename);
}

$fontstats = parse_ini_file('fontcoverage.ini', true);
$langs = languageinfo();

/* Output the first row - font names */
if (isset($fontstats['english'])) {
    foreach($fontstats['english'] as $font => $coverage) {
        printf("    <td>%s</td>\n", getverticalimg($font));
    }
    print("  </tr>\n  </thead>\n  <tbody>\n");
}

foreach($fontstats as $lang => $stats) {
    printf("  <tr>\n    <td class='lang'><img src='flags/%d/%s.png' /> %s</td>\n",SMALL_FLAGSIZE, urlencode($langs[$lang]['flag']), $langs[$lang]['name']);
    foreach($stats as $font => $coverage) {
        if ($coverage == 1) {
            printf("    <td class='full' title='%s has full coverage of %s'>&nbsp;</td>\n", $font, $lang);
        }
        else {
            $r = 0x9A * (1-$coverage);
            $g = 0xBD * (1-$coverage);
            $b = 0xDE * (1-$coverage);
            printf("    <td style='background-color: #%02X%02X%02X' title='%s has %0.2f%% coverage of %s'>&nbsp;</td>", $r, $g, $b, $font, $coverage*100, $lang);
        }
    }
    print("  </tr>\n");
}
?>
</tbody>
</table>
</body>
</html>
