{**
 * templates/index.tpl
 *
 * Copyright (c) 2024+ publicacionesacademicas.es
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Badges form for Insignias Ciencia Abierta plugin.
 *}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Insignias</title>
    <style>
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
                    <label for="url_{$smarty.foreach.foreach.index}">URL:</label>
                    <input type="url" name="badges[{$smarty.foreach.foreach.index}][url]" value="{$badge.url}">
                    <label for="active_{$smarty.foreach.foreach.index}">Activar:</label>
                    <input type="checkbox" name="badges[{$smarty.foreach.foreach.index}][isActive]" id="active_{$smarty.foreach.foreach.index}" {if $badge.isActive}checked{/if}>
                    <input type="hidden" name="badges[{$smarty.foreach.foreach.index}][title]" value="{$badge.title}">
                    <input type="hidden" name="badges[{$smarty.foreach.foreach.index}][description]" value="{$badge.description}">
                    <input type="hidden" name="badges[{$smarty.foreach.foreach.index}][image]" value="{$badge.image}">
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
</html>





