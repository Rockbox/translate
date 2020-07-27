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

require_once('common.php');
print_head();

$languageinfo = languageinfo();

if (isset($_REQUEST['upload']) && is_uploaded_file($_FILES['langfile']['tmp_name'])) {
    $lang = 'upload';
    $phrases = parselangfile($_FILES['langfile']['tmp_name']);
    $languageinfo[$lang]['name'] = $_FILES['langfile']['name'];
}
else {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : '';
    $phrases = parselangfile(sprintf('rockbox/apps/lang/%s.lang', $lang));
}

if ($phrases === false) die("This language doesn't exist, you bad man!");
$english = parselangfile('rockbox/apps/lang/english.lang');

if ($lang == 'english') {
echo <<<MOO
<img class="flag" src="flags/150/{$languageinfo[$lang]['flag']}.png" />
<h1>Potential problems in {$languageinfo[$lang]['name']} language</h1>
<p>The English language file is perfect. Don't taunt it.</p>
<p>Go <a href=".">back</a>.</p>
MOO;
    print_foot();
    die();
}
echo <<<MOO
<img class="flag" src="flags/150/{$languageinfo[$lang]['flag']}.png" />
<h1>Potential problems in {$languageinfo[$lang]['name']} language</h1>
<p>Go <a href=".">back</a>.</p>
<p>This page will help identify potential problems in your language file. I
don't offer a way to fix this automatically, so you'll have to edit the language
file by hand. Hopefully this page will still be of some help. Note that not all
of these lines have to be actual problems, so pay attention to what you do.</p>
MOO;

$strings = array();
foreach($english as $id => $phrase) {
    if (!isset($phrases[$id])) {
        $strings[] = sprintf("<strong>%s</strong>: Missing translation entirely<br />\n",
            $id
        );
    }
    else {
        foreach($phrase['source'] as $target => $value) {
            if (!isset($phrases[$id]['dest'][$target]) && $value != 'none' && $value != '') {
                $strings[] = sprintf("<strong>%s</strong>: Missing target \"%s\"<br />\n",
                    $id, $target
                );
            }
            elseif (
                ($phrases[$id]['dest'][$target] == 'none' || $phrases[$id]['dest'][$target] == '')
                &&
                ($value != 'none' && $value != '')) {
                $strings[] = sprintf("<strong>%s:%s</strong>: Set to \"%s\", but English sets \"%s\"<br />\n",
                    $id, $target, $phrases[$id]['dest'][$target], $value
                );
            }
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Missing strings/targets</h2>\n");
    print("<p>This is an error that should be fixed</p>\n");
    print(join($strings, ''));
}

$strings = array();
foreach($phrases as $id => $phrase) {
    foreach(array("dest", "voice") as $what) {
        foreach($phrase[$what] as $target => $value) {
            if (isset($english[$id][$what][$target])
                && ($english[$id][$what][$target] == '' || $english[$id][$what][$target] == "none")
                && trim($value) != trim($english[$id][$what][$target])
               ) {
                $strings[] = sprintf("<strong>%s:%s - %s</strong>: Empty string doesn't match English (set to: '%s' - should be: '%s')<br />\n",
                    $id,
                    $target,
                    $what,
                    $value == '' ? '""' : $value,
                    $english[$id][$what][$target]
                );
            }
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Wrong empty strings</h2>");
    print("<p>When the English language is set to \"\" or none, the translation should follow. The strings below don't do this. This is an error and should be fixed.</p>\n");
    print(join($strings, ''));
}

$strings = array();
$ignoreidentical = explode("\n",
"LANG_SERIAL_BITRATE_19200
LANG_SERIAL_BITRATE_9600
LANG_SERIAL_BITRATE_38400
LANG_SERIAL_BITRATE_57600
LANG_COMPRESSOR_RATIO_10
LANG_COMPRESSOR_RATIO_2
LANG_COMPRESSOR_RATIO_6
LANG_COMPRESSOR_RATIO_4
LANG_ROCKBOX_TITLE
LANG_EQUALIZER_BAND_Q
LANG_FM_DEFAULT_PRESET_NAME
LANG_DISK_NAME_MMC
LANG_COLOR_RGB_LABELS
LANG_BYTE
LANG_KIBIBYTE
LANG_GIBIBYTE
LANG_USB_HID
VOICE_ZERO
VOICE_ONE
VOICE_TWO
VOICE_THREE
VOICE_FOUR
VOICE_FIVE
VOICE_SIX
VOICE_SEVEN
VOICE_EIGHT
VOICE_NINE
VOICE_TEN
VOICE_ELEVEN
VOICE_TWELVE
VOICE_THIRTEEN
VOICE_FOURTEEN
VOICE_FIFTEEN
VOICE_SIXTEEN
VOICE_SEVENTEEN
VOICE_EIGHTEEN
VOICE_NINETEEN
VOICE_TWENTY
VOICE_THIRTY
VOICE_FORTY
VOICE_FIFTY
VOICE_SIXTY
VOICE_SEVENTY
VOICE_EIGHTY
VOICE_NINETY
VOICE_CHAR_A
VOICE_CHAR_B
VOICE_CHAR_C
VOICE_CHAR_D
VOICE_CHAR_E
VOICE_CHAR_F
VOICE_CHAR_G
VOICE_CHAR_H
VOICE_CHAR_I
VOICE_CHAR_J
VOICE_CHAR_K
VOICE_CHAR_L
VOICE_CHAR_M
VOICE_CHAR_N
VOICE_CHAR_O
VOICE_CHAR_P
VOICE_CHAR_Q
VOICE_CHAR_R
VOICE_CHAR_S
VOICE_CHAR_T
VOICE_CHAR_U
VOICE_CHAR_V
VOICE_CHAR_W
VOICE_CHAR_X
VOICE_CHAR_Y
VOICE_CHAR_Z
VOICE_PAUSE");
foreach($phrases as $id => $phrase) {
    if (in_array($id, $ignoreidentical)) {
        continue;
    }
    foreach($phrase['source'] as $target => $value) {
        if ($phrase['source'][$target] == $phrase['dest'][$target]
            && $phrase['source'][$target] != 'none'
            && $phrase['source'][$target] != ''
            ) {
            $strings[] = sprintf("<strong>%s:%s</strong>: English and translation are the same (\"%s\")<br />\n",
                $id,
                $target,
                $value
            );
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Identical English and translation</h2>");
    printf("<p>Doesn't have to be a problem, if the string is valid in the %s language</p>\n", $languageinfo[$lang]['name']);
    print(join($strings, ''));
}


$strings = array();
foreach($english as $id => $phrase) {
    foreach($phrase['source'] as $target => $value) {
        if (($phrases[$id]['voice'][$target] == ''
                || $phrases[$id]['voice'][$target] == 'none')
                && $phrase['voice'][$target] != ''
                && $phrase['voice'][$target] != 'none'
               ) {
            $strings[] = sprintf("<strong>%s:%s</strong>: Voice missing (english voice: \"%s\")<br />\n",
                $id,
                $target,
                $phrase['voice'][$target]
            );
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Missing voice strings</h2>");
    printf("<p>This is almost certainly a mistake unless the string does not make sense in the %s language, and should be fixed before it's possible to generate meaningful voicefiles for the %s language.</p>\n", $languageinfo[$lang]['name'], $languageinfo[$lang]['name']);
    print(join($strings, ''));
}

$strings = array();
foreach($phrases as $id => $phrase) {
    if (in_array($id, $ignoreidentical)) {
        continue;
    }
    foreach($phrase['source'] as $target => $value) {
        if ($phrase['voice'][$target] == $value && $value != '' && $value != 'none') {
            $strings[] = sprintf("<strong>%s:%s</strong>: Voice and source are the same (\"%s\")<br />\n",
                $id,
                $target,
                $value
            );
        }
        elseif ($english[$id]['voice'][$target] == $phrase['voice'][$target]
                && $english[$id]['voice'][$target] != 'none'
                && $english[$id]['voice'][$target] != ''
               ) {
            $strings[] = sprintf("<strong>%s:%s</strong>: Voice and English voice are the same (\"%s\")<br />\n",
                $id,
                $target,
                $english[$id]['voice'][$target]
            );
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Same voice and source</h2>");
    printf("<p>Doesn't have to be a problem, if the string is valid in the %s language</p>\n", $languageinfo[$lang]['name']);
    print(join($strings, ''));
}

print("<!--\n");
print_r($english);
print("\n-->\n");

$strings = array();
foreach($phrases as $id => $phrase) {
    foreach($phrase['voice'] as $target => $value) {
        if (!isset($english[$id]['voice'][$target])
            && $value != ""
            && $value != "none"
           ) {
            $strings[] = sprintf("<strong>%s:%s</strong>: Voice not defined for English (set to: \"%s\")<br />\n",
                $id,
                $target,
                $value
            );
        }
    }
}
if (sizeof($strings) > 0) {
    print("<h2>Unnecessary voice strings</h2>");
    print("<p>These strings are unnecessary, since they're not defined in the English language file. They should probably be removed</p>\n");
    print(join($strings, ''));
}

if (true || $_SERVER['REMOTE_ADDR'] == trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/homeip'))) {
    echo <<<MOO
    <h2>Check your work in progress</h2>
    <p>Using the form below, you can upload a work in progress and generate a report
    similar to this one, for your language.</p>
    <form enctype="multipart/form-data" action="problems.php" method="post">
    <input type="hidden" name="upload" value="true" />
    <input type="file" name="langfile" />
    <input type="submit" value="send" />
    </form>
MOO;
}

print_foot();
?>
