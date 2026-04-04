<#import "template.ftl" as layout>
<@layout.registrationLayout displayMessage=!messagesPerField.existsError('password','password-confirm'); section>
    <#if section = "header">
        ${msg("updatePasswordTitle")}
    <#elseif section = "form">
        <form id="kc-passwd-update-form" onsubmit="login.disabled = true; return true;" action="${url.loginAction}" method="post" novalidate="novalidate">
            <div class="pso-form-group">
                <label for="password-new" class="pso-label">${msg("passwordNew")}</label>
                <div class="pso-password-group">
                    <input id="password-new" class="pso-input pso-input-password" name="password-new" type="password" autofocus autocomplete="new-password" aria-invalid="<#if messagesPerField.existsError('password')>true</#if>" />
                    <button class="pso-password-toggle" type="button" aria-label="Show password" data-password-toggle aria-controls="password-new" data-icon-show="fa fa-eye" data-icon-hide="fa fa-eye-slash">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <#if messagesPerField.existsError('password')>
                    <span class="pso-error">${messagesPerField.getFirstError('password')}</span>
                </#if>
            </div>

            <div class="pso-form-group">
                <label for="password-confirm" class="pso-label">${msg("passwordConfirm")}</label>
                <div class="pso-password-group">
                    <input id="password-confirm" class="pso-input pso-input-password" name="password-confirm" type="password" autocomplete="new-password" aria-invalid="<#if messagesPerField.existsError('password-confirm')>true</#if>" />
                    <button class="pso-password-toggle" type="button" aria-label="Show password" data-password-toggle aria-controls="password-confirm" data-icon-show="fa fa-eye" data-icon-hide="fa fa-eye-slash">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <#if messagesPerField.existsError('password-confirm')>
                    <span class="pso-error">${messagesPerField.getFirstError('password-confirm')}</span>
                </#if>
            </div>

            <div class="pso-form-group">
                <button id="kc-submit" class="pso-button" name="login" type="submit">${msg("doSubmit")}</button>
            </div>
        </form>
    </#if>
</@layout.registrationLayout>
