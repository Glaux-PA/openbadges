<div>
{foreach from=$badges item=badge}
            <div class="badge-container">
              {if $badge.isActive == true || $badge.isActive == 'true'}
                <a href="{$badge.url}" target="_blank">
                  <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt={$badge.description} style="width:100px;height:auto;">
                </a>
              {/if}
            </div>
        {/foreach}
</div>