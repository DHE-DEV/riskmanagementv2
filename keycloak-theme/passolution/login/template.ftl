<#macro registrationLayout bodyClass="" displayInfo=false displayMessage=true displayRequiredFields=false>
<!DOCTYPE html>
<html lang="${lang}"<#if realm.internationalizationEnabled> dir="${(locale.rtl)?then('rtl','ltr')}"</#if>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${msg("loginTitle",(realm.displayName!''))}</title>
    <link rel="icon" href="${url.resourcesPath}/img/favicon.ico" />
    <#if properties.styles?has_content>
        <#list properties.styles?split(' ') as style>
            <link href="${url.resourcesPath}/${style}" rel="stylesheet" />
        </#list>
    </#if>
    <#if scripts??>
        <#list scripts as script>
            <script src="${script}" type="text/javascript"></script>
        </#list>
    </#if>
    <script type="module" src="${url.resourcesPath}/js/passwordVisibility.js"></script>
</head>
<body>
    <div class="pso-page">
        <div class="pso-container">
            <!-- Logo -->
            <a href="https://platform.passolution.de" class="pso-logo">
                <img src="${url.resourcesPath}/img/logo.png" alt="Passolution" />
            </a>

            <!-- Card -->
            <div class="pso-card">
                <!-- Header -->
                <div class="pso-card-header">
                    <h1 id="kc-page-title"><#nested "header"></h1>
                    <p class="pso-subtitle">${msg("loginSubtitle")}</p>
                </div>

                <!-- Messages -->
                <#if displayMessage && message?has_content && (message.type != 'warning' || !isAppInitiatedAction??)>
                    <div class="pso-alert pso-alert-${message.type}">
                        ${message.summary}
                    </div>
                </#if>

                <!-- Form -->
                <div id="kc-content">
                    <div id="kc-content-wrapper">
                        <#nested "form">
                    </div>
                </div>

                <!-- Social Providers -->
                <#nested "socialProviders">

                <!-- Info -->
                <#if displayInfo>
                    <div class="pso-info">
                        <#nested "info">
                    </div>
                </#if>
            </div>

            <!-- Footer links -->
            <#nested "info">
        </div>
    </div>
</body>
</html>
</#macro>
