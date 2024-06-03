<div class="openBadgesContainer">
  {foreach from=$badges item=badge}
    {if $badge.isActive == true || $badge.isActive == 'true'}
      <div class="badge-container">
        <a href="{$badge.url}" target="_blank">
          <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt={$badge.description}
            style="width:100px;height:auto;">
        </a>
      </div>
    {/if}
  {/foreach}
</div>