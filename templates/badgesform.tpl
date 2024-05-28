{**
 * templates/index.tpl
 *
 * Copyright (c) 2024+ publicacionesacademicas.es
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Badges form for Insignias Ciencia Abierta plugin.
 *}




<head>

    <style type="text/css">
        /* Estilos para el contenedor principal */
        .badge-container {
            display: flex; /* Usa flexbox para crear un layout de dos columnas */
            align-items: center; /* Centra verticalmente los elementos */
        }

        /* Estilos para la columna de la imagen */
        .badge-image {
            flex: 0 0 auto; /* Establece el tamaño de la columna de la imagen */
            margin-right: 20px; /* Añade un margen entre la imagen y los otros campos */
        }

        /* Estilos para la columna de los detalles */
        .badge-details {
            flex: 1 1 auto; /* Establece el tamaño de la columna de los detalles */
        }
    </style>
</head>

<body>
    
    {capture assign=actionUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.openbadges.controllers.OpenBadgesHandler" op="updateBadges" submissionId=$submissionId escape=false}{/capture}

    <form class="pkp_form" method="post" id="badgesForm" action="{$actionUrl}">
    
        {foreach from=$badges item=badge}
            <div class="badge-container">
                <div class="badge-image">
                    <img src="{$pluginBaseUrl|escape}/{$badge.image|escape}" alt="Imagen 2 de {$badge.title}">
                </div>
                <div class="badge-details">
                    <h3>{$badge.title}</h3>
                    <p>{$badge.description}</p>
                    <p>{$badge.id}</p>
                    <p>{$badge.badgeSubmissionID}</p>
                    <p>Activo:</p>
                    <p>{$badge.isActive}</p>
                    <label for="url_{$badge.badgeSubmissionID}">URL:</label>
                    <input type="url" name="badges[{$badge.badgeSubmissionID}][url]" value="{$badge.url}">
                    <label for="active_{$badge.badgeSubmissionID}">Activar:</label>
                    {assign var="isActive" value=$badge.isActive|default:false}
                    <input type="checkbox" name="badges[{$badge.badgeSubmissionID}][isActive]" id="active_{$badge.badgeSubmissionID}" {if $isActive == true || $isActive == 'true' || $isActive === 1 || $isActive === '1'}checked{/if}>
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][title]" value="{$badge.title}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][description]" value="{$badge.description}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][image]" value="{$badge.image}">
                    <input type="hidden" name="badges[{$badge.badgeSubmissionID}][badgeSubmissionID]" value="{$badge.badgeSubmissionID}">
                </div>
            </div>
        {/foreach}
        <!--<input type="hidden" name="accion" value="saveChanges">-->
        <!--<input type="submit" value="Guardar Cambios">-->

        {fbvFormSection class="formButtons"}
            {assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
            {fbvElement type="submit" class="submitFormButton" id=$buttonId label="common.save"}
        {/fbvFormSection}

    </form>
</body>





