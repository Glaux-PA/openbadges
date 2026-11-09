{**
 * plugins/generic/openbadges/templates/block.tpl
 *
 * Copyright (c) 2024+ glaux.es
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