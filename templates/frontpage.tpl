{include file="header.tpl" title=$title rss="rss.php"}

<h1>Rockbox translations</h1>

<p>
On this page you can see the current status of the various Rockbox translations.
Ideally, all languages below should be at 100%. In reality though, this is not
the case. You can help remedy this situation by clicking on the name of a
language you speak and help translate Rockbox all within the comfort of your
webbrowser. Alternatively, you can go to <a
href="//www.rockbox.org/wiki/LangFiles">this page</a> in the
Rockbox wiki, which will tell you how to update languages the more manual, but
possibly slightly safer way.
</p>
<p>You can also find some stats about <a href="//translate.rockbox.org/whichfont.php">font coverage</a>.</p>
<p><em>Note that the Rockbox Utility is translated separately, please see the 
<a href="//www.rockbox.org/wiki/RockboxUtilityDevelopment#How_to_Translate">Rockbox Utility Development</a> page on the wiki.</em></p>

<h2>Current translation status</h2>
<p><i>Note: This is page is updated on the quarter-hour hour after the build farm finishes its post-commit builds.</i></p>
<ul>
{foreach from=$summary key=k item=v}
    <li>{$v} {$k} translation{if $v!=1}s{/if}
    {if $k=="good"}(&gt;85% translated){/if}
    {if $k=="normal"}(&gt;50% translated){/if}
    {if $k=="bad"}(&lt;50% translated){/if}
    </li>
{/foreach}
</ul>
<p><i>Note: Languages need at least 85% coverage before we will enable automatic voicefile creation in nightly and release builds</i></p>

<table>
    <thead>
    <tr>
        <td colspan='3'>Language</td>
        <td>Voiced</td>
        <td>Last update</td>
        <td>Progress</td>
        <td>Phrases missing</td>
        <td>Description changed</td>
        <td>Source changed</td>
        <td>Translation missing or errors</td>
        <td>Voice missing or errors</td>
        <td>Same as English</td>
    </tr>
    </thead>
    {foreach from=$langstats key=langfile item=language}
    {if $language.percentage == 100 && $language.source == 0 && $language.dest == 0 && $language.desc == 0 && $language.voice == 0}
        {assign var='rowclass' value='good'}
    {elseif $language.percentage < 50}
        {assign var='rowclass' value='poor'}
    {else}
        {assign var='rowclass' value=''}
    {/if}
    {if ($language.voicedup + $language.destdup) > 400}
        {assign var='englishclass' value='poor'}
    {elseif ($language.voicedup + $language.destdup) > 100}
        {assign var='englishclass' value='questionable'}
    {else}
        {assign var='englishclass' value=''}
    {/if}

    <tr class="{$rowclass}">
        <td>
            <img class="flagthumb" src="flags/22/{$language.flag}.png" />
        </td>
        <td>
	{if $langfile != 'english' && $language.percentage < 100}
            <a href='problems.php?lang={$langfile}'><img style='border: none' src='warning.gif' width='16' height='16' /></a>
        {/if}
        </td>
        <td>
        {if $language.percentage == 100 && $language.desc == 0 && $language.source == 0 && $language.dest == 0 && $language.voice == 0 && ($language.voicedup + $language.destdup) == 0 }
            {$language.name}
        {else}
            <a href='edit.php?lang={$langfile}'>{$language.name}</a>
        {/if}
        </td>
        <td>
        {if $language.voiced}
            {$language.voiced}
        {/if}
        </td>
        <td>
            <a href='//git.rockbox.org/cgit/rockbox.git/commit/?id={$language.last_update_rev}' title='{$language.last_update|date_format:"%c"}'>
                {$language.last_update|simple_timesince}
            </a>
        </td>
        <td><img title='{$language.percentage|string_format:"%.2f%%"}' src='graph.php?p={$language.percentage|string_format:"%.2f"}' /></td>
        </td>
        <td>{$language.missing}</td>
        <td>{$language.desc}</td>
        <td>{$language.source}</td>
        <td>{$language.dest}</td>
        <td>{$language.voice}</td>
        <td class="{$englishclass}">{$language.voicedup + $language.destdup}</td>
    </tr>
    {/foreach}
</table>

{include file="footer.tpl"}
