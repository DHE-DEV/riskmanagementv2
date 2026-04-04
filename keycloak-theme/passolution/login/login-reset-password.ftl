<#import "template.ftl" as layout>
<@layout.registrationLayout displayInfo=true displayMessage=!messagesPerField.existsError('username'); section>
    <#if section = "header">
        ${msg("emailForgotTitle")}
    <#elseif section = "form">
        <form id="kc-reset-password-form" action="${url.loginAction}" method="post">
            <div class="pso-form-group">
                <label for="username" class="pso-label">
                    <#if !realm.loginWithEmailAllowed>${msg("username")}<#elseif !realm.registrationEmailAsUsername>${msg("usernameOrEmail")}<#else>${msg("email")}</#if>
                </label>
                <input id="username" class="pso-input" name="username" value="${auth.attemptedUsername!''}" type="text" autofocus autocomplete="username" />
                <#if messagesPerField.existsError('username')>
                    <span class="pso-error">${messagesPerField.getFirstError('username')}</span>
                </#if>
            </div>

            <div class="pso-form-group">
                <button class="pso-button" type="submit">${msg("doSubmit")}</button>
            </div>
            <div class="pso-form-group" style="margin-top: 0.75rem;">
                <a href="${url.loginUrl}" class="pso-button-secondary">${msg("backToLogin")}</a>
            </div>
        </form>
    <#elseif section = "info">
        <#if realm.duplicateEmailsAllowed>
            ${msg("emailInstructionUsername")}
        <#else>
            ${msg("emailInstruction")}
        </#if>
    </#if>
</@layout.registrationLayout>
