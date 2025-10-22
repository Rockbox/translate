#!/usr/bin/perl -w
##################
# *             __________               __   ___.
# *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
# *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
# *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
# *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
# *                     \/            \/     \/    \/            \/
# * Copyright (C) 2020 Solomon Peachy
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

use strict;
use feature 'unicode_strings';
use Unicode::Normalize;
use Unicode::UCD 'charprop';
binmode(STDOUT, ":utf8");

my @langs;
my @fonts;

my %fontchars;
my %langchars;
my %missing;

sub fontcoverage($) {
    my ($font) = @_;

    open(FILE, "<rockbox/fonts/$font") || die ("can't open $font!\n");
    while(<FILE>) {
	if (/^ENCODING\s+(\d+)\s*/) {
	    $fontchars{$font}{chr($1)} = $1;
	}
    }
    close(FILE);
}

sub langcoverage($) {
    my ($lang) = @_;
    $lang =~ s/(.*)\.lang/$1/;

    open(FILE, "<rockbox/apps/lang/$lang.lang") || die ("can't open $lang!\n");
    binmode(FILE, ":utf8");
    my $indest = 0;
    while(<FILE>) {
	if (/^\s*<dest>\s*$/) {
	    $indest = 1;
	} elsif (/^\s*<\/dest>\s*$/) {
	    $indest = 0;
	} elsif ($indest) {
	    if (/\s*\S*\s*:\s*"(\S*)"\s*/u) {
		next if ($1 eq "none");
		foreach my $char (split(//, $1)) {
# XXX do we necessarily always want to both decomp and not?
# Revisit this after we get utf8proc into the core?
		    if (!defined($langchars{$lang}{$char})) {
			$langchars{$lang}{$char} = 0;
		    }
		    $langchars{$lang}{$char}++;
		    my $decomp = NFD($char);
		    foreach my $d (split(//, $decomp)) {
			if (!defined($langchars{$lang}{$d})) {
			    $langchars{$lang}{$d} = 0;
			}
			$langchars{$lang}{$d}++;
		    }
		}
	    }
	}
    }
    close(FILE);
}

sub calccoverage($$) {
    my ($lang, $font) = @_;
    my $total = 0;
    my $covered = 0;
    my $str = "";

    foreach my $l (sort(keys(%{$langchars{$lang}}))) {
	next if ($l eq " ");
	$str .= $l;
	$total++;
	#$total += $langchars{$lang}{$l};
	if (defined($fontchars{$font}{$l})) {
	    $covered++;
	    #$covered += $langchars{$lang}{$l};
	} else {
	    $missing{$font}{$lang}{$l} = 1;
	}
    }

#    return "$covered/$total - '$str'";
    return $covered/$total;
}

###################

# Populate font and language lists
opendir(DIR, "rockbox/apps/lang");
@langs = grep(/\.lang$/,readdir(DIR));
closedir(DIR);

opendir(DIR, "rockbox/fonts");
@fonts = grep(/\.bdf$/,readdir(DIR));
closedir(DIR);

# Generate coverage maps
foreach my $x (@fonts) {
    fontcoverage($x);
}
foreach my $x (@langs) {
    langcoverage($x);
}

# Geneate INI files
# (standard summary)
foreach my $lang (sort(@langs)) {
    $lang =~ s/(.*)\.lang/$1/;
    print "[$lang]\n";
    foreach my $font (sort(@fonts)) {
	my $coverage = calccoverage($lang, $font);
        $font =~/(.*).bdf/;
	printf "  $1 = %1.6f\n", $coverage;
    }
}

# (missing)
foreach my $lang (sort(@langs)) {
    $lang =~ s/(.*)\.lang/$1/;
    print "[missing|$lang]\n";
    foreach my $font (sort(@fonts)) {
        $font =~/(.*).bdf/;
	my $str = "";
	foreach my $miss (sort(keys(%{$missing{$font}{$lang}}))) {
            my $prop = charprop(ord($miss), "combining");
            my $miss2 = $miss;
            $miss2 = " $miss" if (defined($prop));
	    $str .= "u+".ord($miss)."[$miss2] ";
	}
	print "  $1 = $str\n" if ($str);
    }
}
