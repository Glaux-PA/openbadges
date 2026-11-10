{**
 * plugins/generic/openbadges/templates/badgesform.tpl
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Badges form for Insignias Ciencia Abierta plugin.
 *}

{capture assign=actionUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.openbadges.controllers.OpenBadgesHandler" op="updateBadges" submissionId=$submissionId escape=false}{/capture}

<form class="pkpForm pkp_form" method="post" id="badgesForm" action="{$actionUrl}">
    {csrf}
    <fieldset class="pkpFormGroup -pkpClearfix">
        {foreach from=$badges item=badge}
            <div class="badge-container">
                <div class="badge-image">
                    <img src="{$pluginBaseUrl|escape}/{$pluginurl|escape}{translate key=$badge.image}" alt="{translate key=$badge.title|escape}">
                </div>
                <div class="badge-details">
                    <h3>{translate key=$badge.title}</h3>
                    <p>{translate key=$badge.description}</p>

                    <div class="badge-url">
                        <label for="url_{$badge.id}">URL:</label>
                        <input type="text" name="badges[{$badge.id}][url]" id="url_{$badge.id}" pattern="^(#|https?:\/\/.*)$" value="{$badge.url|escape}">
                    </div>
                    <div class="badge-status">
                        <label for="active_{$badge.id}">{translate key="plugins.generic.openbadges.activeLabel"}</label>
                        {assign var="isActive" value=$badge.isActive|default:false}
                        <input type="checkbox" name="badges[{$badge.id}][isActive]"
                            id="active_{$badge.id}"
                            {if $isActive == true || $isActive === 1}checked{/if}>
                    </div>
                </div>
            </div>
        {/foreach}
    </fieldset>
    <div aria-live="polite" class="pkpFormPage__footer">
        {fbvFormSection class="formButtons"}
        {assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
        {fbvElement type="submit" class="submitFormButton" id=$buttonId label="common.save"}
        {/fbvFormSection}
    </div>
</form>