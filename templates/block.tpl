<ul class="open-badges-container">
  {foreach from=$badges item=badge}
    {if $badge.isActive == true || $badge.isActive == 'true'}
      <li class="badge-item">
        <a href="{$badge.url}" target="_blank">
          <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt={$badge.description}>
        </a>
      </li>
    {/if}
  {/foreach}
</ul>