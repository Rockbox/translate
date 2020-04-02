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

    $height = 15;
    $width = 150;
    $percent = $_REQUEST['p'];
    $im = imagecreatetruecolor($width, $height);
    $translated = imagecolorallocate($im, 0, 255, 0);
    $missing = imagecolorallocate($im, 255, 0, 0);
    imagefill($im, 0, 0, $missing);
    imagefilledrectangle($im, 0, 0, $width * $percent / 100, $height, $translated);
    header("Content-type: image/png");
    imagepng($im);
?>
