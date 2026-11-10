{**
 * plugins/generic/openbadges/templates/block.tpl
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * 
 *}

<ul class="open-badges-container">
  {foreach from=$badges item=badge}
    {if $badge.isActive == true}
      <li class="badge-item">
        <a href="{$badge.url|escape}" target="_blank" rel="noopener">
        <img src="{$pluginBaseUrl|escape}/{$pluginurl|escape}{translate key=$badge.image}" alt="{translate key=$badge.title|escape}">
        </a>
      </li>
    {/if}
  {/foreach}
</ul>