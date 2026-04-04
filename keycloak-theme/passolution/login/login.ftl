<#import "template.ftl" as layout>
<@layout.registrationLayout displayMessage=!messagesPerField.existsError('username','password') displayInfo=realm.password && realm.registrationAllowed && !registrationDisabled??; section>

    <#if section = "header">
        ${msg("loginAccountTitle")}
    <#elseif section = "form">
        <div id="kc-form">
            <div id="kc-form-wrapper">
                <#if realm.password>
                    <form id="kc-form-login" onsubmit="login.disabled = true; return true;" action="${url.loginAction}" method="post" novalidate="novalidate">
                        <#if !usernameHidden??>
                            <div class="pso-form-group">
                                <label for="username" class="pso-label">
                                    <#if !realm.loginWithEmailAllowed>${msg("username")}<#elseif !realm.registrationEmailAsUsername>${msg("usernameOrEmail")}<#else>${msg("email")}</#if>
                                </label>
                                <input tabindex="2" id="username" class="pso-input" name="username" value="${login.username!''}" type="text" autofocus autocomplete="username" aria-invalid="<#if messagesPerField.existsError('username','password')>true</#if>" placeholder="${msg("usernameOrEmail")}" />
                                <#if messagesPerField.existsError('username','password')>
                                    <span class="pso-error">${messagesPerField.getFirstError('username','password')}</span>
                                </#if>
                            </div>
                        </#if>

                        <div class="pso-form-group">
                            <div class="pso-label-row">
                                <label for="password" class="pso-label">${msg("password")}</label>
                                <#if realm.resetPasswordAllowed>
                                    <a href="${url.loginResetCredentialsUrl}" class="pso-forgot">${msg("doForgotPassword")}</a>
                                </#if>
                            </div>
                            <div class="pso-password-group">
                                <input tabindex="3" id="password" class="pso-input pso-input-password" name="password" type="password" autocomplete="current-password" aria-invalid="<#if messagesPerField.existsError('username','password')>true</#if>" placeholder="${msg("password")}" />
                                <button class="pso-password-toggle" type="button" aria-label="Show password" aria-controls="password" data-password-toggle tabindex="4" data-icon-show="fa fa-eye" data-icon-hide="fa fa-eye-slash" data-label-show="Show password" data-label-hide="Hide password">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>

                        <#if realm.rememberMe && !usernameHidden??>
                            <div class="pso-form-group pso-remember">
                                <input tabindex="5" id="rememberMe" name="rememberMe" type="checkbox" <#if login.rememberMe??>checked</#if>>
                                <label for="rememberMe">${msg("rememberMe")}</label>
                            </div>
                        </#if>

                        <div class="pso-form-group">
                            <input type="hidden" id="id-hidden-input" name="credentialId" <#if auth.selectedCredential?has_content>value="${auth.selectedCredential}"</#if>/>
                            <button tabindex="7" class="pso-button" name="login" id="kc-login" type="submit">${msg("doLogIn")}</button>
                        </div>
                    </form>
                </#if>
            </div>
        </div>
    <#elseif section = "socialProviders">
        <#if realm.password && social.providers?? && social.providers?has_content>
            <div class="pso-divider">
                <span>oder</span>
            </div>
            <div class="pso-social">
                <#list social.providers as p>
                    <a href="${p.loginUrl}" class="pso-social-button" id="social-${p.alias}" aria-label="${p.displayName}">
                        <span>${p.displayName}</span>
                    </a>
                </#list>
            </div>
        </#if>
    <#elseif section = "info">
        <#if realm.password && realm.registrationAllowed && !registrationDisabled??>
            <div class="pso-register">
                <span>${msg("noAccount")} <a href="${url.registrationUrl}">${msg("doRegister")}</a></span>
            </div>
        </#if>
    </#if>

</@layout.registrationLayout>
