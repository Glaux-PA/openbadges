<ul class="openBadgesContainer">
  {foreach from=$badges item=badge}
    {if $badge.isActive == true || $badge.isActive == 'true'}
      <li class="badge-container">
        <a href="{$badge.url}" target="_blank">
          <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt={$badge.description}
            style="width:100px;height:auto;">
        </a>
      </li>
    {/if}
  {/foreach}
</ul>