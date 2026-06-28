<?php

namespace App\Http\Responses;

use App\Http\Controllers\Auth\Customer\Concerns\ResolvesSsoLogout;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    use ResolvesSsoLogout;

    /**
     * Nach dem (Fortify-)Logout zum SSO-Logout-Endpunkt leiten, damit auch die
     * geteilte Homepage-Session (pds_sso_session) beendet wird (Single-Logout).
     * Ist kein SSO_LOGOUT_URL gesetzt, faellt ssoLogoutUrl() auf das Standard-Ziel
     * (Login-Seite) zurueck.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return redirect($this->ssoLogoutUrl());
    }
}
