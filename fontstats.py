#!/usr/bin/env python2
# -*- coding: utf8 -*-
##################################
# *             __________               __   ___.
# *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
# *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
# *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
# *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
# *                     \/            \/     \/    \/            \/
# * Copyright (C) 2010 Jonas Häggqvist
# *
# * This program is free software; you can redistribute it and/or
# * modify it under the terms of the GNU General Public License
# * as published by the Free Software Foundation; either version 2
# * of the License, or (at your option) any later version.
# *
# * This software is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY
# * KIND, either express or implied.
# *
##################################

import re
import sys
import locale
import codecs
from glob import glob
from os.path import basename, dirname, join
from pprint import pprint

def langs():
    return glob(join(dirname(__file__), 'rockbox/apps/lang/*.lang'))

def fonts():
    return glob(join(dirname(__file__), 'rockbox/fonts/*.bdf'))

def charusage(langfile):
    usage = {}
    fp = codecs.open(langfile, 'r', 'UTF-8')
    indest = False
    for line in fp:
        if re.match(r'^\s*<dest>\s*$', line):
            indest = True
        elif re.match(r'^\s*</dest>\s*$', line):
            indest = False

        if indest:
            string = re.match(r'\s*\S*\s*:\s*"([^"]*)"\s*', line)
            if string:
                for char in string.group(1):
                    if char not in usage:
                        usage[char] = 0
                    usage[char] += 1
    return usage

def charsavailable(fontfile):
    chars = []
    fp = open(fontfile, 'r')
    for line in fp:
        encoding = re.match(r'ENCODING\s+(\d+)\s*', line)
        if encoding:
            chars.append(unichr(int(encoding.group(1))))
    return chars

def calculatecoverage(charsused, charsavailable):
    total = 0
    covered = 0
    for char, uses in charsused.iteritems():
        if char == u' ':
            continue
        total += uses
        if char in charsavailable:
            covered += uses
    return float(covered)/float(total)

def generate_summary(fontstats, langusage):
    for langfile, charsused in sorted(langusage.items()):
        print "[%s]" % basename(langfile).replace('.lang', '')
        for fontfile, charsavailable in sorted(fontstats.items()):
            coverage = calculatecoverage(charsused, charsavailable)
            print "  %s = %f" % (basename(fontfile).replace('.bdf', ''), coverage)

def generate_missing(fontstats, langusage):
    for langfile, charsused in sorted(langusage.items()):
        print "[%s]" % basename(langfile).replace('.lang', '')
        for fontfile, charsavailable in sorted(fontstats.items()):
            missingchars = []
            for char, uses in charsused.iteritems():
                if char not in charsavailable:
                    missingchars.append(char)
            # If more than 50 characters are missing, don't print them all
            if 25 > len(missingchars) > 0:
                print "  %s = %s" % (basename(fontfile).replace('.bdf', ''), " ".join(["%s (u+%X)" % (c, ord(c)) for c in missingchars]))


if __name__ == '__main__':
    sys.stdout = codecs.getwriter(locale.getpreferredencoding())(sys.stdout);

    fontstats = dict([(font, charsavailable(font)) for font in fonts()])
    langusage = dict([(lang, charusage(lang)) for lang in langs()])

    if len(sys.argv) > 1 and sys.argv[1] == 'missing':
        generate_missing(fontstats, langusage)
    else:
        generate_summary(fontstats, langusage)
