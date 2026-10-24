<?php

namespace App\Services\Push;

use App\Models\Push\AppDevice;
use Illuminate\Support\Facades\DB;

/**
 * Identitaet uebernehmen (Wiederherstellungscode bzw. bestaetigte Uebertragung): Der ALTE Geraete-Eintrag bleibt
 * mit ID, App-ID, Anzeigename, Gruppen, geteilten Reisen, Reise-Links und Sicherung erhalten. Er bekommt ein neues
 * Secret und Plattform, Push-Token, Sprache und App-Version der neuen Installation. Der frisch angelegte Eintrag der
 * neuen Installation wird geloescht. Die alte Installation bekommt danach 403 (Secret ungueltig).
 * Der Wiederherstellungscode bleibt gueltig.
 */
class DeviceAdoption
{
    /**
     * @return string neues Secret (Klartext, nur fuer die Antwort an die neue Installation)
     */
    public function adopt(AppDevice $old, AppDevice $new): string
    {
        abort_if($old->id === $new->id, 422, 'Same device.');

        return DB::transaction(function () use ($old, $new) {
            [$secret, $hash] = AppDevice::newSecret();
            $attributes = [
                'platform' => $new->platform,
                'push_token' => $new->push_token,
                'push_environment' => $new->push_environment,
                'locale' => $new->locale,
                'app_version' => $new->app_version,
            ];

            // Token vom neuen Eintrag loesen, dann den neuen Eintrag samt allem, was er schon hat, entfernen.
            $new->forceFill(['push_token' => null])->save();
            $new->delete();

            if (filled($attributes['push_token'])) {
                AppDevice::where('push_token', $attributes['push_token'])->where('id', '!=', $old->id)->update(['push_token' => null]);
            }

            $old->forceFill($attributes + [
                'secret_hash' => $hash,
                'last_seen_at' => now(),
                'token_invalid_at' => null,
            ])->save();

            return $secret;
        });
    }
}
