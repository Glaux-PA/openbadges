{**
 * templates/index.tpl
 *
 * Copyright (c) 2024+ publicacionesacademicas.es
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Badges form for Insignias Ciencia Abierta plugin.
 *}

{capture assign=actionUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.openbadges.controllers.OpenBadgesHandler" op="updateBadges" submissionId=$submissionId escape=false}{/capture}

<form class="pkpForm pkp_form" method="post" id="badgesForm" action="{$actionUrl}">
    <fieldset class="pkpFormGroup -pkpClearfix">
        {foreach from=$badges item=badge}
            <div class="badge-container">
                <div class="badge-image">
                    <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt="Imagen 2 de {$badge.title}">
                </div>
                <div class="badge-details">
                    <h3>{$badge.title}</h3>
                    <p>{$badge.description}</p>

                    <div class="badge-url">
                        <label for="url_{$badge.badgeSubmissionID}">URL:</label>
                        <input type="url" name="badges[{$badge.badgeSubmissionID}][url]" value="{$badge.url}">
                    </div>
                    <div class="badge-status">
                        <label for="active_{$badge.badgeSubmissionID}">Activar</label>
                        {assign var="isActive" value=$badge.isActive|default:false}
                        <input type="checkbox" name="badges[{$badge.badgeSubmissionID}][isActive]"
                            id="active_{$badge.badgeSubmissionID}"
                            {if $isActive == true || $isActive == 'true' || $isActive === 1 || $isActive === '1'}checked{/if}>
                    </div>
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][title]" value="{$badge.title}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][description]"
                        value="{$badge.description}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][image]" value="{$badge.image}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][badgeSubmissionID]"
                        value="{$badge.badgeSubmissionID}">
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