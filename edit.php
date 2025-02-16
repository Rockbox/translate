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

function edit($lang, $all = false) {
    $languageinfo = languageinfo();
    $LARGE_FLAGSIZE = LARGE_FLAGSIZE;
    echo <<<END
<img class="flag" src="flags/{$LARGE_FLAGSIZE}/{$languageinfo[$lang]['flag']}.png" />
<h1>Edit {$languageinfo[$lang]['name']} language</h1>
<p>Go <a href="index.php">back</a>.</p>
<p>
Go through this list and follow the instructions given <span class="note">marked in dark red</span>. When
you're done, press the "Finish translating" button at the bottom of this page.<!-- ' -->
You will then be sent a patch file with your changes. Submit this file
to Rockbox on the <a href="//www.rockbox.org/tracker/newtask/proj1">patch
tracker</a>.
</p>
<p>Other notes:</p>
<ul>
<li><b>We will need your full legal name in order to accept any patches, including translation updates!</b></li>
<li>If a field is read-only, it means that this string is not meant to be translated and will not be editable.</li>
<li>The special string "none" is not meant to be translated, and will not allow edits.</li>
<li>Normally, if a translated string is the same as the original English, this is treated as an error. To bypass this test for specific strings, please prepend them with '~'. (For example, instead of 'something' you would write '~something')</li>
<li>Rules are stricter for 'voice' entries, as they are intended to be *spoken*. </li>
<li>Voice strings cannot include C format specifiers (eg '%d') and other suspicious characters such as quotation marks or ellipses.</li>
<li>There is a set of corrections automatically applied to voice strings; for example spelling out common acronyms or fixing mispronciations in a TTS engine.  See <a href="https://git.rockbox.org/cgit/rockbox.git/tree/tools/voice-corrections.txt">voice-corrections.txt</a> for specifics.</li>
END;

if ($all == false) {
  print("<li>This view only shows the strings that are known to be potentially problematic.  To see a complete listing, click <a href=\"edit.php?lang=$lang&all=true\">here</a>.</li>\n");
}

echo <<<END
</ul>
<form action="submit.php" method="post">
<input type="hidden" name="lang" value="$lang" />
END;

    $phrases = parselangfile(sprintf("scratch/%s.lang.update", $lang), $all);
    $english = parselangfile(sprintf("scratch/%s.lang.update", 'english'), $all);
    if ($phrases === false || $english === false) {
        printf("<strong>The file %s.lang doesn't exist, or something else went terribly wrong</strong>", $lang);
        return false;
    }

    $inputlang = isset($languageinfo[$lang]['code']) && $languageinfo[$lang]['code'] != '' ? sprintf(" lang='%s' ", $languageinfo[$lang]['code']) : '';
    $inputdir = isset($languageinfo[$lang]['rtl']) && $languageinfo[$lang]['rtl'] === true ? " dir='rtl' " : '';
    foreach($phrases as $id => $phrase) {
        $foo = array();
        if (sizeof($phrase['notes']) > 0 && trim(strtolower($phrase['phrase']['desc'])) != "deprecated") {
            printf("<h3>%s</h3>", $phrase['phrase']['id']);

            if (isset($phrase['phrase']['desc']))
                printf("Description: %s<br />\n", $phrase['phrase']['desc']);
            if (isset($phrase['phrase']['user']) && $phrase['phrase']['user'] != '')
                printf("User: %s<br />\n", $phrase['phrase']['user']);

            if (sizeof($phrase['notes']) > 0) {
                print("<div class='note'>");
                foreach($phrase['notes'] as $line) {
		    if (preg_match('/The <(.+)> section for \'.+:(.*)\'.+/', $line, $matches)) {
	                $foo["$matches[1]:$matches[2]"] = 1;
	            } elseif (preg_match('/This phrase is missing/', $line)) {
	                $foo["dest"] = 1;
	                $foo["voice"] = 1;
	            }
                    printf("%s<br />\n", htmlspecialchars($line));
                }
                print("</div>");
            }

            printf("<table><thead><tr><td>Target/feature</td><td>English string</td><td>%s translation</td><td>English voice</td><td>%s voice</td></tr></thead>", $languageinfo[$lang]['name'], $languageinfo[$lang]['name']);
            foreach($phrase['source'] as $target => $string) {
                // Figure out what to put in the translated string
                if (isset($english[$id]['dest'][$target]) && ($english[$id]['dest'][$target] == '' || $english[$id]['dest'][$target] == 'none')) {
                    $translated_value = $phrase['dest'][$target] = $english[$id]['dest'][$target];
                }
                elseif (isset($phrase['dest'][$target]) && $phrase['dest'][$target] != '' && $phrase['dest'][$target] != 'none') {
                    $translated_value = $phrase['dest'][$target];
                }
                else {
                    $translated_value = '';
                }

                // Figure out whether to set the translated value readonly
                if (
                    // If english string is either unset, '' or none
                    (!isset($english[$id]['source'][$target]) || $english[$id]['source'][$target] == '' || $english[$id]['source'][$target] == 'none')
                    &&
                    // And destination is either unset or set to '' or none
                    (!isset($phrase['dest'][$target]) || ($phrase['dest'][$target] == '' || $phrase['dest'][$target] == 'none'))
                   ) {
                    $translated_readonly = 'readonly="readonly" class="readonly"';
                }
                else {
                    $translated_readonly = '';
                }

                // Figure out what to put in the voice string
                if (isset($english[$id]['voice'][$target]) && ($english[$id]['voice'][$target] == '' || $english[$id]['voice'][$target] == 'none')) {
                    $voice_value = $phrase['voice'][$target] = $english[$id]['voice'][$target];
                }
                elseif (isset($phrase['voice'][$target]) && $phrase['voice'][$target] != '' && $phrase['voice'][$target] != 'none') {
                    $voice_value = $phrase['voice'][$target];
                }
                else {
                    $voice_value = '';
                }

                // Figure out whether to set the voice value readonly
                if (
                    // If english voice is either unset, '' or none
                    (!isset($english[$id]['voice'][$target]) || $english[$id]['voice'][$target] == '' || $english[$id]['voice'][$target] == 'none')
                    &&
                    ($id != 'VOICE_NUMERIC_TENS_SWAP_SEPARATOR')
                    &&
                    // And voice is not set, or set to '' or none
                    (!isset($phrase['voice'][$target]) || ($phrase['voice'][$target] == '' || $phrase['voice'][$target] == 'none'))
                    ) {
                    $voice_readonly = 'readonly="readonly" class="readonly"';
                }
                else {
                    $voice_readonly = '';
                }

                print("<tr>");
		if (array_key_exists("dest:$target", $foo) ||
		    array_key_exists("dest", $foo)) {
			$bgcolor='class="poor"';
                } else {
			$bgcolor='';
		}

                printf("<td>%s</td><td>%s</td><td $bgcolor><input %s %s name='phrases[%s][dest][%s]' size='40' type='text' value='%s' %s /></td>",
                    htmlspecialchars($target),
                    htmlspecialchars($string),
                    $inputlang,
                    $inputdir,
                    htmlspecialchars($id),
                    htmlspecialchars($target),
                    htmlspecialchars($translated_value, ENT_QUOTES),
                    $translated_readonly
                );

		if (array_key_exists("voice:$target", $foo) ||
		    array_key_exists("voice", $foo)) {
			$bgcolor='class="poor"';
                } else {
			$bgcolor='';
		}


                if (!isset($english[$id]['voice'][$target])) {
                    print("<td colspan='2'></td>");
                }
                else {
                    printf("<td>%s</td><td $bgcolor><input %s %s name='phrases[%s][voice][%s]' size='40' type='text' value='%s' %s /></td>",
                        htmlspecialchars(isset($english[$id]['voice'][$target]) ? $english[$id]['voice'][$target] : ''),
                        $inputlang,
                        $inputdir,
                        htmlspecialchars($id),
                        htmlspecialchars($target),
                        htmlspecialchars($voice_value, ENT_QUOTES),
                        $voice_readonly
                    );
                }
                print("</tr>\n");
            }
            print("</table>");
        }
        elseif (trim(strtolower($phrase['phrase']['desc'])) == "deprecated") {
            printf("<input type='hidden' name='phrases[%s][dest][*]' value='' /><input type='hidden' name='phrases[%s][dest][*]' value='' />",
                htmlspecialchars($id),
                htmlspecialchars($id)
            );
        }
    }
echo <<<END
<br/>
<table cols="2">
<tr>
<td><input type="submit" value="Create Patch" name"patch" style="margin-top: 1em" /></td>
<td>
When you click this button, you will be sent a patch against the currently-committed language file. If you are
satisfied with your changes, you are encouraged to submit this file in the <a href="//www.rockbox.org/tracker/newtask/proj1">Rockbox patch
tracker.</a>
</td>
</tr>
<tr>
<td><input type="submit" value="Create Full" name="full" style="margin-top: 1em" /></td>
<td>
When you click this button, you will be sent the entire language file incorporating your changes.
</td>
</tr>
</table>
</form>
END;
}

print_head();
edit($_REQUEST['lang'], $_REQUEST['all']);
print_foot();
?>
