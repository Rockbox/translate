<?php
/***************************************************************************
 *             __________               __   ___.
 *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
 *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
 *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
 *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
 *                     \/            \/     \/    \/            \/
 * $Id$
 *
 * Copyright (C) 2009 Jonas Häggqvist
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This software is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY
 * KIND, either express or implied.
 *
 ****************************************************************************/

function qs($count) {
    return $count == 1 ? '' : 's';
}

class templater {
    private $s;

    public function __construct($smartydir) {
        /* Load and set up Smarty */
        require_once(sprintf("%s/Smarty.class.php", $smartydir));
        $s = new smarty();
        $s->setTemplateDir("templates");
        $s->setCompileDir("templates/compiled");
        $s->setCacheDir("templates/cache");
//        $s->caching = false;
//        $s->debugging = false;
//        $s->security = true;
//        $s->security_settings['IF_FUNCS'] = array('array_key_exists', 'isset', 'is_array', 'count', 'file_exists');
//        $s->secure_dir = realpath($s->template_dir);
//        $s->register_modifier('simple_timesince', array(&$this, 'simple_timesince'));
	$s->registerPlugin("modifier","simple_timesince", array(&$this, "simple_timesince"));
        $this->s = $s;
    }

    public function simple_timesince($timestamp) {
        $seconds = time() - $timestamp;
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


    public function assign($name, $value) {
        $this->s->assign($name, $value);
    }

    public function render($pagename, $vars = array()) {
        if (is_array($vars)) {
            foreach($vars as $name => $value) {
                $this->assign($name, $value);
            }
        }
        $this->s->display($pagename);
    }
}
?>
