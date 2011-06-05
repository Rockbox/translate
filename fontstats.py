#!/usr/bin/env python2.6
import re
import codecs
from glob import glob
from os.path import basename
from pprint import pprint

def langs():
    return glob('rockbox/apps/lang/*.lang')

def fonts():
    return glob('rockbox/fonts/*.bdf')

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

fontstats = dict([(font, charsavailable(font)) for font in fonts()])
langusage = dict([(lang, charusage(lang)) for lang in langs()])

for langfile, charsused in sorted(langusage.items()):
    print "[%s]" % basename(langfile).replace('.lang', '')
    for fontfile, charsavailable in sorted(fontstats.items()):
        coverage = calculatecoverage(charsused, charsavailable)
        print "  %s = %f" % (basename(fontfile).replace('.bdf', ''), coverage)