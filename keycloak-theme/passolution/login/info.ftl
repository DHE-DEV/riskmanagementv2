<#import "template.ftl" as layout>
<@layout.registrationLayout displayMessage=false; section>
    <#if section = "header">
        <#if messageHeader??>
            ${kcSanitize(msg("${messageHeader}"))?no_esc}
        <#else>
            ${message.summary}
        </#if>
    <#elseif section = "form">
        <div class="pso-info-message">
            <p>${message.summary}<#if requiredActions??><#list requiredActions>: <b><#items as reqActionItem>${kcSanitize(msg("requiredAction.${reqActionItem}"))?no_esc}<#sep>, </#items></b></#list><#else></#if></p>
            <#if skipLink??>
            <#else>
                <#if pageRedirectUri?has_content>
                    <p style="margin-top: 1rem;"><a href="${pageRedirectUri}" class="pso-button-secondary">${msg("backToApplication")}</a></p>
                <#elseif actionUri?has_content>
                    <p style="margin-top: 1rem;"><a href="${actionUri}" class="pso-button">${msg("proceedWithAction")}</a></p>
                <#elseif (client.baseUrl)?has_content>
                    <p style="margin-top: 1rem;"><a href="${client.baseUrl}" class="pso-button-secondary">${msg("backToApplication")}</a></p>
                </#if>
            </#if>
        </div>
    </#if>
</@layout.registrationLayout>
