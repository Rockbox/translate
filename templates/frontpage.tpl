{include file="header.tpl" title=$title rss="rss.php"}

<h1>Rockbox translations</h1>

<p>
On this page you can see the current status of the various Rockbox translations.
Ideally, all languages below should be at 100%. In reality though, this is not
the case. You can help remedy this situation by clicking on the name of a
language you speak and help translate Rockbox all within the comfort of your
webbrowser. Alternatively, you can go to <a
href="//www.rockbox.org/twiki/bin/view/Main/LangFiles">this page</a> in the
Rockbox wiki, which will tell you how to update languages the more manual, but
possibly slightly safer way.
</p>
<p>You can also find some stats about <a href="//translate.rockbox.org/whichfont.php">font coverage</a>.</p>
<p><em>Note that the Rockbox Utility is translated separately, please see the 
<a href="//www.rockbox.org/wiki/Main/RockboxUtilityDevelopment#How_to_Translate">Rockbox Utility Development</a> page on the wiki.</em></p>

<h2>Current translation status</h2>
<p><i>Note: This is updated at most every 15 minutes, but only if the build farm is idle.</i></p>
<ul>
{foreach from=$summary key=k item=v}
    <li>{$v} {$k} translations
    {if $k=="good"}(&gt;95% translated){/if}
    {if $k=="normal"}(&gt;50% translated){/if}
    {if $k=="bad"}(&lt;50% translated){/if}
    </li>
{/foreach}
</ul>

<table>
    <thead>
    <tr>
        <td colspan='4'>Language</td>
        <td>Last update</td>
        <td>Progress</td>
        <td>Missing phrases</td>
        <td>Changed description</td>
        <td>Changed source</td>
        <td>Missing translation</td>
        <td>Missing voice</td>
        <td>Same as English</td>
    </tr>
    </thead>
    {foreach from=$langstats key=langfile item=language}
    {if $language.percentage == 100}
        {assign var='rowclass' value='good'}
    {elseif $language.percentage < 50}
        {assign var='rowclass' value='poor'}
    {else}
        {assign var='rowclass' value=''}
    {/if}
    <tr class="{$rowclass}">
        <td>
            <img class="flagthumb" src="flags/22/{$language.flag}.png" />
        </td>
        <td>
	{if $langfile != 'english'}
            <a href='problems.php?lang={$langfile}'><img style='border: none' src='warning.gif' width='16' height='16' /></a>
        {/if}
        </td>
        <td>
        {if file_exists('graphs/$langfile')}
            <a href='graphs/{$langfile}.png'><img style='border: none' src='graph.png' width='16' height='16' /></a>
        {/if}
        </td>
        <td>
        {if $language.percentage == 100 && $language.desc == 0 && $language.source == 0 && $language.dest == 0 && $language.voice == 0}
            {$language.name}
        {else}
            <a href='edit.php?lang={$langfile}'>{$language.name}</a>
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
        <td>{$language.voicedup + $language.destdup}</td>
    </tr>
    {/foreach}
</table>

<h2>Perform automated cleanup</h2>
<p>Using the form below, it's possible to perform automated cleanups of a
translation. Be aware though, that this might produce unwanted results in some
cases, so you're required to check the results rather than blindly trusting
them.</p>

<form action="fixlang.php" method="GET">
<table>
    <tr>
        <td>Language</td>
        <td>
            <select name="lang">
            {foreach from=$languages item=language key=langfile}
                {if $langfile != 'upload'}
                <option value='{$langfile}'>{$language.name}</option>
                {/if}
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <label for='voice' title='Copy translation to voice for phrases where string and voice are the same in the English language file'>Copy voice strings</label>
        </td>
        <td>
            <input type='checkbox' id='voice', name='voice' title='Copy translation to voice for phrases where string and voice are the same in the English language file' />
        </td>
    </tr>
    <tr>
        <td>
            <label for='empty' title='Make empty and "none" strings match the English language file'>Fix empty strings</label>
        </td>
        <td>
            <input type='checkbox' id='empty', name='empty' title='Make empty and "none" strings match the English language file' />
        </td>
    </tr>
    <tr>
        <td>
            <label for='sort' title='Sort phrases in the same order as the English language file'>Sort in English order</label>
        </td>
        <td>
            <input type='checkbox' id='sort', name='sort' title='Sort phrases in the same order as the English language file' />
        </td>
    </tr>
    <tr>
        <td>
            <label for='sendfile' title='Prompt to save the result on disk'>Save result as file</label>
        </td>
        <td>
            <input type='checkbox' id='sendfile', name='sendfile' title='Prompt to save the result on disk' />
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><input type="submit" /></td>
    </tr>
</table>
</form>

</table>

{include file="footer.tpl"}
