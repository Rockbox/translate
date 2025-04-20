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
    $langfile = $_FILES['langfile']['tmp_name'];
} else {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : '';
    $langfile = sprintf('rockbox/apps/lang/%s.lang', $lang);
}

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
<p>This page will help identify potential problems in your language file.
This doesn't offer a way to fix this automatically, so you'll have to edit
the language file yourself.</p>
MOO;

$cmd = sprintf("%s -s rockbox/tools/updatelang rockbox/apps/lang/english.lang $langfile -", PERL);
$output = shell_exec($cmd);

$missing = array();
$missing_sub = array();
$source = array();
$other = array();
$desc = array();
$identical = array();

foreach(explode("\n", $output) as $line) {
  $line = trim($line);
  if (preg_match("/^### (.*)$/", $line, $matches)) {
    if (preg_match("/^The phrase '(.*)' is missing entirely/", $matches[1], $phrase)) {
      $missing[] = $phrase[1];
    } elseif (preg_match("/^The 'desc' field for '(.*)'/", $matches[1], $phrase)) {
      $desc[] = $phrase[1];
    } elseif (preg_match("/^The <source> section for '(.*)' differs from/", $matches[1], $phrase)) {
      $source[] = $phrase[1];
    } elseif (preg_match("/^The <(.*)> section for '(.*)' is identical/", $matches[1], $phrase)) {
      $identical[] = "$phrase[2] ($phrase[1])";
    } elseif (preg_match("/^The <(.*)> section for '(.*)' is missing/", $matches[1], $phrase)) {
      if ($phrase[1] == 'source') { continue; }
      $missing_sub[] = "$phrase[2] ($phrase[1])";
    } elseif (preg_match("/^The <(.*)> section for '(.*)' is blank/", $matches[1], $phrase)) {
      $missing_sub[] = "$phrase[2] ($phrase[1])";
    } else {
      $other[] = htmlentities($matches[1]);
    }
  }
}

if (count($missing)) {
  print "<h3>Phrases missing entirely:</h3>\n";
  print "<ul>\n";
  foreach ($missing as $line) {
    print "<li>$line</li>\n";
  }
  print "</ul>\n";
}

if (count($missing_sub)) {
  print "<h3>Sub-phrases missing:</h3>\n";
  print "<ul>\n";
  foreach ($missing_sub as $line) {
    print "<li>$line</li>\n";
  }
  print "</ul>\n";
}

if (count($desc)) {
  print "<h3>Phrases with a changed description:</h3>\n";
  print "<ul>\n";
  foreach ($desc as $line) {
    print "<li>$line</li>\n";
  }
  print "</ul>\n";
}

if (count($source)) {
  print "<h3>Phrases with a changed source:</h3>\n";
  print "<ul>\n";
  foreach ($source as $line) {
    print "<li>$line</li>\n";
  }
  print "</ul>\n";
}

if (count($identical)) {
  print "<h3>Translated strings that are the same as English:</h3>\n";
  print "<ul>\n";
  foreach ($identical as $line) {
    print "<li>$line</li>\n";
  }
  print "</ul>\n";
}

if (count($other)) {
  print "<h3>Other problems:</h3>\n";
  print "<ul>\n";
  foreach ($other as $line) {
    print "<li>$line</li>\n";
 }
 print "</ul>\n";
}



print "<h2>Other actions</h2>\n";
if (!isset($_REQUEST['upload'])) {
  print "<p>Normally the translation edit page only allows flagged/problematic entries to be edited. To enable editing the entire translation, follow <a href='edit.php?lang=$lang&all=1'>this link</a></p>\n";
}

if (true || $_SERVER['REMOTE_ADDR'] == trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/homeip'))) {
    echo <<<MOO
    <h2>Check your work in progress</h2>
    <p>Using the form below, you can upload a work in progress and generate a report
    similar to this one, for your language.</p>
    <form enctype="multipart/form-data" action="problems2.php" method="post">
    <input type="hidden" name="upload" value="true" />
    <input type="file" name="langfile" />
    <input type="submit" value="send" />
    </form>
MOO;
}

print_foot();
?>
