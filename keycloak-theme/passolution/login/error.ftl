<#import "template.ftl" as layout>
<@layout.registrationLayout displayMessage=false; section>
    <#if section = "header">
        ${kcSanitize(msg("errorTitle"))?no_esc}
    <#elseif section = "form">
        <div class="pso-info-message">
            <p>${kcSanitize(message.summary)?no_esc}</p>
            <#if skipLink??>
            <#else>
                <#if client?? && client.baseUrl?has_content>
                    <p style="margin-top: 1rem;">
                        <a href="${client.baseUrl}" class="pso-button-secondary">${msg("backToApplication")}</a>
                    </p>
                </#if>
            </#if>
        </div>
    </#if>
</@layout.registrationLayout>
