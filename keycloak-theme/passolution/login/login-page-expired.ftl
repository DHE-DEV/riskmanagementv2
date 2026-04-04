<#import "template.ftl" as layout>
<@layout.registrationLayout; section>
    <#if section = "header">
        ${msg("pageExpiredTitle")}
    <#elseif section = "form">
        <div class="pso-info-message">
            <p>${msg("pageExpiredMsg1")} <a href="${url.loginRestartFlowUrl}">${msg("doClickHere")}</a></p>
            <p style="margin-top: 0.75rem;">${msg("pageExpiredMsg2")} <a href="${url.loginAction}">${msg("doClickHere")}</a></p>
        </div>
    </#if>
</@layout.registrationLayout>
