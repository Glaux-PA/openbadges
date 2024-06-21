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
        <a href="{$badge.url}" target="_blank">
        <img src="{$pluginBaseUrl|escape}/{$pluginurl|escape}{translate key=$badge.image|escape}" alt={translate key=$badge.description}">
        </a>
      </li>
    {/if}
  {/foreach}
</ul>