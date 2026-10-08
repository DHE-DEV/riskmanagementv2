<?php

use App\Models\MobileOperator;
use Illuminate\Database\Migrations\Migration;

/**
 * Website und Logo der Mobilfunkanbieter – ueber die Wikipedia-Artikel der
 * Anbieter aus Wikidata (offizielle Website P856, Logo P154 auf Wikimedia
 * Commons, Stand Oktober 2026). Es wird nur gefuellt, was leer ist; bei
 * Marken mit mehreren Laendern (Orange, Vodafone, MTN, …) gilt die
 * Konzern-Website und wird gesetzt, auch wenn schon eine Laender-Website
 * stand. Prepaid-/Touristentarif-Seiten gibt es dort nicht und bleiben zur
 * Pflege.
 */
return new class extends Migration
{
    /** Marken mit mehreren Laendern: Website immer auf die Konzernseite setzen */
    private const GROUPS = ['Orange', 'Vodafone', 'MTN', 'Airtel', 'Claro', 'Digicel', 'FLOW', 'Tigo', 'Ooredoo', 'A1', 'Zain', 'Movistar', 'Vodacom', 'Telia', 'Tele2', 'Sure', 'Moov', 'Telenor', 'O2', 'Etisalat'];

    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        foreach (self::DETAILS as $name => $row) {
            $operator = MobileOperator::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

            if (! $operator) {
                continue;
            }

            $changes = [];

            foreach (['website_url', 'logo_url'] as $field) {
                $force = $field === 'website_url' && in_array($name, self::GROUPS, true);

                if ((blank($operator->{$field}) || $force) && isset($row[$field])) {
                    $changes[$field] = $row[$field];
                }
            }

            if ($changes !== []) {
                $operator->forceFill($changes)->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        // Website und Logo lassen sich von Hand pflegen – ein Zuruecknehmen wuerde auch das treffen.
    }

    /** @var array<string, array{website_url?: string, logo_url?: string}> Name => Website und Logo */
    private const DETAILS = [
        '1&1' => [
            'website_url' => 'https://www.1und1.ag/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/ab/1%261_Drillisch_Logo_2017.png',
        ],
        '2degrees' => [
            'website_url' => 'https://www.2degrees.nz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/2d_logo.svg',
        ],
        '3 Mob' => [
            'website_url' => 'http://3mob.ua/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b1/Trimoblogo.png',
        ],
        'A-Mobile' => [
            'website_url' => 'http://a-mobile.biz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7c/A-Mobile_logo.svg',
        ],
        'A1' => [
            'website_url' => 'https://www.a1.group',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/98/A1_red_logo.png',
        ],
        'Afghan Wireless' => [
            'website_url' => 'https://afghan-wireless.com/',
        ],
        'Africell' => [
            'website_url' => 'http://www.africell.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/99/Logoafricell.png',
        ],
        'Airtel' => [
            'website_url' => 'https://www.airtel.africa',
        ],
        'Airtel Kenya' => [
            'website_url' => 'https://www.airtelkenya.com/',
        ],
        'Airtel Uganda' => [
            'website_url' => 'http://www.africa.airtel.com/uganda/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/da/Airtel_Africa_logo.svg',
        ],
        'Airtel-Vodafone' => [
            'website_url' => 'https://www.airtel-vodafone.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Vodafone_logo_2017.svg',
        ],
        'AIS' => [
            'website_url' => 'https://www.ais.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/3b/Advanced_Info_Service_logo.svg',
        ],
        'AJ SIM' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'Alfa' => [
            'website_url' => 'https://www.alfa.com.lb/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d9/Logo_text_Alfa_Telecom_%28Lebanon%29.png',
        ],
        'Algar Telecom' => [
            'website_url' => 'http://www.algartelecom.com.br/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bc/Algar_Telecom_logo.svg',
        ],
        'Aliv' => [
            'website_url' => 'https://www.bealiv.com/',
        ],
        'altice' => [
            'website_url' => 'http://www.altice.com.do',
        ],
        'Andorra Telecom' => [
            'website_url' => 'https://www.andorratelecom.ad/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/9c/Andorra_Telecom_2019_logo.svg',
        ],
        'Antel' => [
            'website_url' => 'https://www.antel.com.uy/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bf/Antel.svg',
        ],
        'Aquafon' => [
            'website_url' => 'http://www.aquafon.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6c/Aquafonlogo.png',
        ],
        'Asia Cell' => [
            'website_url' => 'https://www.asiacell.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/Asiacell_logo.svg',
        ],
        'AT&T' => [
            'website_url' => 'https://www.att.com/wireless/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/AT%26T_logo_2016.svg',
        ],
        'AT&T Mexico' => [
            'website_url' => 'http://www.att.com.mx/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/AT%26T_logo_2016.svg',
        ],
        'ATOM' => [
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/22/ATOM_logo.png',
        ],
        'au' => [
            'website_url' => 'https://www.au.com/english/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5e/Au_2012_newlogo.svg',
        ],
        'Azercell' => [
            'website_url' => 'https://www.azercell.com/az/',
        ],
        'azur' => [
            'website_url' => 'http://www.nationlinktelecom.com',
        ],
        'B-Mobile' => [
            'website_url' => 'http://www.bt.bt',
        ],
        'Banglalink with Digital Sub Brand - RYZE' => [
            'website_url' => 'https://www.banglalink.net/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6d/Banglalink_Logo_2025.svg',
        ],
        'Base' => [
            'website_url' => 'http://www.base.be/en.html',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/BASE_logo.svg',
        ],
        'Batelco' => [
            'website_url' => 'http://www.batelcogroup.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b4/Batelco_logo.JPG',
        ],
        'beeline' => [
            'website_url' => 'https://www.veon.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7a/BeeLine_logo.png',
        ],
        'Beeline Kazakhstan' => [
            'website_url' => 'https://www.beeline.ru/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7a/BeeLine_logo.png',
        ],
        'Bell' => [
            'website_url' => 'https://www.bell.ca/mobility',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5a/Bell_Mobility_logo.svg',
        ],
        'BH Telecom' => [
            'website_url' => 'http://www.bhtelecom.ba',
        ],
        'Bitė' => [
            'website_url' => 'https://www.bitegroup.net/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/ce/Bit%C4%97_Group_logotipas.png',
        ],
        'bmobile' => [
            'website_url' => 'http://bmobile.co.tt/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/45/Bmobile.png',
        ],
        'Bouygues Telecom' => [
            'website_url' => 'https://www.bouyguestelecom.fr',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f8/Bouygues_Telecom_201x_logo.svg',
        ],
        'BSNL' => [
            'website_url' => 'https://www.bsnl.co.in',
        ],
        'BTC' => [
            'website_url' => 'http://www.btcbahamas.com/',
        ],
        'C Spire' => [
            'website_url' => 'https://www.cspire.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/67/C_Spire.png',
        ],
        'Cable & Wireless' => [
            'website_url' => 'https://www.cwc.com/',
        ],
        'Camtel' => [
            'website_url' => 'http://www.camtel.cm/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/19/Logos-Camtel-03-blue-2.png',
        ],
        'CelcomDigi' => [
            'website_url' => 'https://corporate.celcomdigi.com/',
        ],
        'Cell C' => [
            'website_url' => 'http://www.cellc.co.za/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b6/Cell_C_New_2024_logo.svg',
        ],
        'Cellcard' => [
            'website_url' => 'http://www.cellcard.com.kh',
        ],
        'Cellcom' => [
            'website_url' => 'https://www.cellcom.co.il',
        ],
        'Cellfie' => [
            'website_url' => 'https://www.beeline.ru/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7a/BeeLine_logo.png',
        ],
        'CHiLi' => [
            'website_url' => 'http://www.chili.mu/',
        ],
        'China Mobile' => [
            'website_url' => 'http://www.10086.cn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Mobile_World_Congress_2017.jpg',
        ],
        'China Mobile CMLink SG' => [
            'website_url' => 'http://www.10086.cn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Mobile_World_Congress_2017.jpg',
        ],
        'China Telecom' => [
            'website_url' => 'https://www.chinatelecom-h.com',
        ],
        'China Unicom' => [
            'website_url' => 'http://www.chinaunicom.com/',
        ],
        'China Unicom CUniq SG' => [
            'website_url' => 'http://www.chinaunicom.com/',
        ],
        'Chunghwa Telecom' => [
            'website_url' => 'https://www.cht.com.tw',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2a/Chunghwa_Telecom.svg',
        ],
        'Circles.Life' => [
            'website_url' => 'https://www.circles.life/sg/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/33/Circles.Life_Logo.png',
        ],
        'Claro' => [
            'website_url' => 'https://www.claro.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/cc/Claro_logo_%282017%29.svg',
        ],
        'CMHK' => [
            'website_url' => 'https://www.hk.chinamobile.com/',
        ],
        'Cnt' => [
            'website_url' => 'http://www.cnt.gob.ec/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/CNT_Logo.svg',
        ],
        'Contact Mobile' => [
            'website_url' => 'https://one.nz',
        ],
        'Crnogorski Telekom' => [
            'website_url' => 'https://www.telekom.me/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Telekom_Logo_2013.svg',
        ],
        'csl' => [
            'website_url' => 'https://www.hkcsl.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Csl_New_Logo.jpg',
        ],
        'CTM' => [
            'website_url' => 'https://www.ctm.net/',
        ],
        'Cuy Móvil' => [
            'website_url' => 'https://www.claro.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/cc/Claro_logo_%282017%29.svg',
        ],
        'Cyta' => [
            'website_url' => 'http://www.cyta.com.cy/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d7/Cytamobile-Vodafone_logo.jpg',
        ],
        'Datastream Digital' => [
            'website_url' => 'https://dst.com.bn/',
        ],
        'Dhiraagu' => [
            'website_url' => 'http://www.dhiraagu.com.mv/',
        ],
        'Dialog' => [
            'website_url' => 'https://www.dialog.lk/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/45/Dialog_Axiata_logo.svg',
        ],
        'DIGI' => [
            'website_url' => 'https://www.digi-communications.ro/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a5/Digi_2024_Logo.svg',
        ],
        'Digicel' => [
            'website_url' => 'https://www.digicelgroup.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/49/Digicel_logo.svg',
        ],
        'Digital Nasional Berhad' => [
            'website_url' => 'http://www.digital-nasional.com.my',
        ],
        'Digitel GSM' => [
            'website_url' => 'http://www.digitel.com.ve/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/ba/Digitel_%282014%29.svg',
        ],
        'Disney Mobile' => [
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Disney_Mobile_logo.png',
        ],
        'Dito Telecommunity' => [
            'website_url' => 'https://dito.ph',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/fb/Dito-logo.svg',
        ],
        'Djezzy' => [
            'website_url' => 'https://www.djezzy.dz/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Djezzy_Logo_2015.svg',
        ],
        'DNA' => [
            'website_url' => 'http://www.dna.fi/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/74/DNA_Oyj_logo.svg',
        ],
        'Docomo Pacific' => [
            'website_url' => 'http://www.docomopacific.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/55/DoCoMo_Pacific_logo.svg',
        ],
        'Drei' => [
            'website_url' => 'https://www.three.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/40/Three_logo.svg',
        ],
        'du' => [
            'website_url' => 'https://www.du.ae/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0e/Du_Solid_Brandmark_RGB.png',
        ],
        'Econet' => [
            'website_url' => 'http://www.econetwireless.com/',
        ],
        'Econet Telecom Lesotho' => [
            'website_url' => 'http://www.etl.co.ls/',
        ],
        'EE' => [
            'website_url' => 'https://www.ee.co.uk/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2f/EE_logo.svg',
        ],
        'eir' => [
            'website_url' => 'https://www.eir.ie',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/34/Eircom_bag.JPG',
        ],
        'Elisa' => [
            'website_url' => 'https://www.elisa.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/Elisa.svg',
        ],
        'Emtel' => [
            'website_url' => 'http://www.emtel.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/Emtel_Logo.png',
        ],
        'Entel' => [
            'website_url' => 'https://www.entel.bo/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8b/Entel-2021.png',
        ],
        'Epic' => [
            'website_url' => 'https://www.epic.com.cy/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/dd/MTN_logo.png',
        ],
        'Eritel' => [
            'website_url' => 'http://www.tse.com.er',
        ],
        'ETB' => [
            'website_url' => 'http://www.etb.com.co',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/30/ETB_Bogot%C3%A1_logo.svg',
        ],
        'Ethiotelecom' => [
            'website_url' => 'https://www.ethiotelecom.et/',
        ],
        'Etisalat' => [
            'website_url' => 'https://eand.com.eg',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b9/Etisalat_EN_Logo.svg',
        ],
        'Etisalat Afghanistan' => [
            'website_url' => 'https://www.eand.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b9/Etisalat_EN_Logo.svg',
        ],
        'Far EasTone' => [
            'website_url' => 'https://www.fetnet.net/',
        ],
        'Fastweb + Vodafone' => [
            'website_url' => 'https://www.fastweb.it/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/dd/Logo_Fastweb_2020.svg',
        ],
        'Feels' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'FLOW' => [
            'website_url' => 'https://discoverflow.co',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/56/Flow_%28brand%29_logo.svg',
        ],
        'FREE' => [
            'website_url' => 'https://www.free.fr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/52/Free_logo.svg',
        ],
        'Free Mobile' => [
            'website_url' => 'https://mobile.free.fr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1d/Free_mobile_2011.svg',
        ],
        'Føroya Tele' => [
            'website_url' => 'https://www.ft.fo/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/46/F%C3%B8roya_Tele_logo.svg',
        ],
        'G-Mobile' => [
            'website_url' => 'https://gmobile.mn/',
        ],
        'Gamcel' => [
            'website_url' => 'http://www.gamtel.gm',
        ],
        'GCI' => [
            'website_url' => 'http://www.gci.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a8/GCI_logo.svg',
        ],
        'Gibtelecom' => [
            'website_url' => 'http://www.gibtele.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0d/Gibtelecom_2017_logo.svg',
        ],
        'Glo' => [
            'website_url' => 'https://www.gloworld.com/',
        ],
        'Glo Mobile' => [
            'website_url' => 'https://www.gloworld.com/',
        ],
        'Globe Telecom' => [
            'website_url' => 'https://www.globe.com.ph',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Globe_Telecom_logo.svg',
        ],
        'GO' => [
            'website_url' => 'http://www.go.com.mt/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/ae/GO_Logo.svg',
        ],
        'Golis Telecom Somalia' => [
            'website_url' => 'http://www.golistelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f0/Golis_Telecom_Somalia.png',
        ],
        'GOMO' => [
            'website_url' => 'https://www.globe.com.ph',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Globe_Telecom_logo.svg',
        ],
        'Grameenphone -with Digital Sub Brand -Skitto' => [
            'website_url' => 'http://grameenphone.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/98/Grameenphone_Logo_GP_Logo.svg',
        ],
        'GTA' => [
            'website_url' => 'http://www.gta.net/',
        ],
        'Halotel' => [
            'website_url' => 'http://halotel.co.tz/',
        ],
        'Hamrahe Aval' => [
            'website_url' => 'http://www.mci.ir/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0a/MCI_logo_%282025%29.svg',
        ],
        'HONDUTEL' => [
            'website_url' => 'http://www.hondutel.hn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8e/Logo-hondutel.svg',
        ],
        'Hormuud' => [
            'website_url' => 'https://www.hormuud.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/dd/Hormuud_logo.png',
        ],
        'Hot Mobile' => [
            'website_url' => 'https://www.hotmobile.co.il/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b9/Mirs-Logo.svg',
        ],
        'Hrvatski Telekom' => [
            'website_url' => 'https://www.hrvatskitelekom.hr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d7/T-Hrvatski_Telekom_Logo.svg',
        ],
        'HT ERONET' => [
            'website_url' => 'http://www.hteronet.ba/',
        ],
        'Hutch' => [
            'website_url' => 'https://www.hutch.lk',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/da/Hutch_Logo.svg',
        ],
        'i-Kool 3G' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'Ibon Mobile' => [
            'website_url' => 'https://www.fetnet.net/',
        ],
        'Ice' => [
            'website_url' => 'https://ice.no',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/93/Ice_logo.svg',
        ],
        'IEC3G Buzzme' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'Iliad' => [
            'website_url' => 'https://www.iliad.it/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6c/Iliad_logo.svg',
        ],
        'imobile-3GX' => [
            'website_url' => 'http://www.cattelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/CAT_Logo.svg',
        ],
        'Indosat Ooredoo Hutchison' => [
            'website_url' => 'https://indosatooredoo.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/fb/Indosat_Ooredoo_Hutchison.svg',
        ],
        'Interdnestrcom' => [
            'website_url' => 'http://www.idknet.com',
        ],
        'inwi' => [
            'website_url' => 'https://inwi.ma/ar',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ef/Logo_inwi.svg',
        ],
        'IPKO' => [
            'website_url' => 'http://www.ipko.com/',
        ],
        'Irancell' => [
            'website_url' => 'http://www.irancell.ir/en',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7d/Irancell_Logo.gif',
        ],
        'Jawwal' => [
            'website_url' => 'http://www.jawwal.ps',
        ],
        'Jazz' => [
            'website_url' => 'http://www.jazz.com.pk/',
        ],
        'Jio' => [
            'website_url' => 'http://www.jio.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bf/Reliance_Jio_Logo.svg',
        ],
        'JT' => [
            'website_url' => 'http://www.jtglobal.com',
        ],
        'K-Telecom' => [
            'website_url' => 'http://mobile-win.ru',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/62/%D0%92%D0%B8%D0%BD_%D0%BC%D0%BE%D0%B1%D0%B0%D0%B9%D0%BB_%2B7%D0%A2%D0%B5%D0%BB%D0%B5%D0%BA%D0%BE%D0%BC.png',
        ],
        'Kcell' => [
            'website_url' => 'http://www.kcell.kz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0b/Kcell_wordmark.svg',
        ],
        'KiQ' => [
            'website_url' => 'https://smart.com.ph/',
        ],
        'Kiwi Mobile' => [
            'website_url' => 'https://www.2degrees.nz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/2d_logo.svg',
        ],
        'Kogan Mobile' => [
            'website_url' => 'https://one.nz',
        ],
        'Korek' => [
            'website_url' => 'https://www.korektele.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a7/Korek_Telecom_Logo.png',
        ],
        'KPN' => [
            'website_url' => 'https://www.kpn.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/ca/KPN-Logo.svg',
        ],
        'KT' => [
            'website_url' => 'http://www.kt.com/eng/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/22/KT_Corp_2D_logo.svg',
        ],
        'Kyivstar' => [
            'website_url' => 'https://www.kyivstar.ua/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/cd/Kyivstar.svg',
        ],
        'Letai' => [
            'website_url' => 'http://tatarstan.ru/',
        ],
        'LG U' => [
            'website_url' => 'http://www.uplus.co.kr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/LG_U%2B_CI.svg',
        ],
        'Liberty' => [
            'website_url' => 'http://www.onelinkpr.net',
        ],
        'Libyana' => [
            'website_url' => 'https://www.libyana.ly/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/da/Libyana_mobile_phone_logo.png',
        ],
        'lifecell' => [
            'website_url' => 'https://www.lifecell.ua/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/86/Lifecell_2016_logo_-_Wordmark.svg',
        ],
        'LMT' => [
            'website_url' => 'https://www.lmt.lv/',
        ],
        'Lonestar Cell MTN' => [
            'website_url' => 'http://www.lonestarcell.com/',
        ],
        'LTC Mobile' => [
            'website_url' => 'https://www.libtelco.com.lr',
        ],
        'Lumitel' => [
            'website_url' => 'https://www.lumitel.bi/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/LUMITEL_LOGO-01.jpg',
        ],
        'M1' => [
            'website_url' => 'http://www.m1.com.sg',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f8/M1_Singapore_2020.svg',
        ],
        'm:tel' => [
            'website_url' => 'https://mtel.ba/',
        ],
        'Magenta Telekom' => [
            'website_url' => 'https://www.magenta.at/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d3/Magenta_Telekom.svg',
        ],
        'Magticom' => [
            'website_url' => 'http://www.magticom.ge/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1a/Magti-_2020-logo.jpg',
        ],
        'Magyar Telekom' => [
            'website_url' => 'https://www.telekom.hu/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Telekom_Logo_2013.svg',
        ],
        'Manx Telecom' => [
            'website_url' => 'http://www.manx-telecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/cb/Manx_telecom_new_logo.svg',
        ],
        'Maroc Telecom' => [
            'website_url' => 'https://www.iam.ma',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/de/Maroc_Telecom_logo.png',
        ],
        'Mascom' => [
            'website_url' => 'https://www.mascom.bw/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/35/Mascom_logo.png',
        ],
        'Mauritius Telecom' => [
            'website_url' => 'https://www.telecom.mu',
        ],
        'Maxis' => [
            'website_url' => 'https://www.maxis.com.my/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/76/Maxis-logo.png',
        ],
        'MegaFon' => [
            'website_url' => 'http://megafon.ru/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d1/MegaFon_logo_Russian.svg',
        ],
        'Melita' => [
            'website_url' => 'https://www.melita.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/78/Melita_logo.svg',
        ],
        'MEO' => [
            'website_url' => 'https://www.meo.pt/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0d/Logo_MEO.svg',
        ],
        'Mighty Mobile' => [
            'website_url' => 'https://one.nz',
        ],
        'Miranda' => [
            'website_url' => 'https://www.miranda-media.ru/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ec/%D0%9C%D0%B8%D1%80%D0%B0%D0%BD%D0%B4%D0%B0-%D0%9C%D0%B5%D0%B4%D0%B8%D0%B0.svg',
        ],
        'MKS' => [
            'website_url' => 'https://главалнр.рф/',
        ],
        'Mobicom' => [
            'website_url' => 'http://www.mobicom.mn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/16/Mobicomnewlogo.png',
        ],
        'MobiFone' => [
            'website_url' => 'http://www.mobifone.com.vn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a0/MobiFone_logo.svg',
        ],
        'Mobilis' => [
            'website_url' => 'https://www.mobilis.dz/',
        ],
        'Mobily' => [
            'website_url' => 'https://www.mobily.com.sa/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/33/MobilyLogo.svg',
        ],
        'Mobiuz' => [
            'website_url' => 'http://ums.uz/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d8/Mobiuz.svg',
        ],
        'Mojo 3G' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'Moldcell' => [
            'website_url' => 'http://www.moldcell.md',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e9/Moldcell.png',
        ],
        'Moldtelecom' => [
            'website_url' => 'https://www.moldtelecom.md/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/4f/Moldtelecom_logo_April_2021.png',
        ],
        'Monaco Telecom' => [
            'website_url' => 'https://www.monaco-telecom.mc/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/32/Logo_Monaco_Telecom_2019.svg',
        ],
        'Moov' => [
            'website_url' => 'https://www.moov-africa.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/72/Moov_new_logo.png',
        ],
        'Moov Africa Gabon Telecom' => [
            'website_url' => 'http://www.gabontelecom.ga/',
        ],
        'Moov Africa Malitel' => [
            'website_url' => 'https://www.moov-africa.ml/',
        ],
        'Moov Mauritel' => [
            'website_url' => 'http://www.mauritel.mr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e8/Moov_Mauritel_logo_%28French_version%29.svg',
        ],
        'MOTIV' => [
            'website_url' => 'http://www.midural.ru',
        ],
        'Movicel' => [
            'website_url' => 'http://www.movicel.co.ao/',
        ],
        'Movilnet' => [
            'website_url' => 'http://www.cantv.com.ve/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/21/CANTV_logo.svg',
        ],
        'Movistar' => [
            'website_url' => 'https://www.movistar.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/88/Movistar_isotype_2025.png',
        ],
        'Movitel' => [
            'website_url' => 'http://www.movitel.co.mz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f7/Movitel-Logo.jpg',
        ],
        'MPT' => [
            'website_url' => 'http://www.myanmaposts.net.mm/',
        ],
        'MTC' => [
            'website_url' => 'http://www.mtc.com.na/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/49/Mtc_Namibia_Logo.svg',
        ],
        'MTN' => [
            'website_url' => 'https://www.mtn.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/af/MTN_Logo.svg',
        ],
        'MTN Rwanda' => [
            'website_url' => 'https://www.mtn.co.rw/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2a/MTN_2022_logo.svg',
        ],
        'MTN Syria' => [
            'website_url' => 'http://mtn.com.sy',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/af/MTN_Logo.svg',
        ],
        'MTN Uganda' => [
            'website_url' => 'http://www.mtn.co.ug/',
        ],
        'MTNL' => [
            'website_url' => 'http://www.mtnl.net.in/',
        ],
        'MTS' => [
            'website_url' => 'https://www.mts.ru/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/86/MTS_logo_2015.svg',
        ],
        'MTS DOO' => [
            'website_url' => 'https://telekomsrbija.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a4/2020_%D0%A2%D0%B5%D0%BB%D0%B5%D0%BA%D0%BE%D0%BC_%D0%A1%D1%80%D0%B1%D0%B8%D1%98%D0%B0_%D0%BB%D0%BE%D0%B3%D0%BE.png',
        ],
        'my by NT' => [
            'website_url' => 'http://www.cattelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/CAT_Logo.svg',
        ],
        'MyRepublic' => [
            'website_url' => 'https://myrepublic.co.id/',
        ],
        'Mytel' => [
            'website_url' => 'http://mytel.com.mm/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8c/The_Mytel_Logo.png',
        ],
        'MyWorld 3G' => [
            'website_url' => 'http://www.cattelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/CAT_Logo.svg',
        ],
        'Móvil Éxito' => [
            'website_url' => 'https://www.grupoexito.com.co/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/03/Grupo_Exito_logo.svg',
        ],
        'Nakhtel' => [
            'website_url' => 'https://naxtel.az/',
        ],
        'Nar' => [
            'website_url' => 'http://www.nar.az',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6e/Nar-new-logo.jpg',
        ],
        'Nationlink' => [
            'website_url' => 'http://www.nationlinktelecom.com',
        ],
        'Ncell' => [
            'website_url' => 'http://www.ncell.com.np',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/4b/Ncell_logo.svg',
        ],
        'Nema' => [
            'website_url' => 'https://nema.fo/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a3/Nema_2019_logo.svg',
        ],
        'Nepal Telecom' => [
            'website_url' => 'http://www.ntc.net.np',
        ],
        'NetOne' => [
            'website_url' => 'http://www.netone.co.zw/',
        ],
        'Nexttel' => [
            'website_url' => 'https://viettel.vn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d5/Viettel_Group_en_logo.svg',
        ],
        'Niger Telecoms' => [
            'website_url' => 'https://www.nigertelecoms.ne/',
        ],
        'Norlys' => [
            'website_url' => 'https://norlys.dk/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1b/Norlys_2020_Logo.svg',
        ],
        'NOS' => [
            'website_url' => 'https://www.nos.pt/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/4d/NOS_Portugal_logo.svg',
        ],
        'Nova' => [
            'website_url' => 'https://www.nova.gr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/df/NOVA_Greece_logo.svg',
        ],
        'Now Telecom' => [
            'website_url' => 'http://www.now-telecom.com/',
        ],
        'NT Mobile' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'NTT Docomo' => [
            'website_url' => 'https://www.docomo.ne.jp/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/cd/NTT_Docomo_2025.svg',
        ],
        'O2' => [
            'website_url' => 'https://www.telefonica.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/60/O2.svg',
        ],
        'Odido' => [
            'website_url' => 'https://www.odido.nl/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a5/Odido_2023_Logo.png',
        ],
        'Omantel' => [
            'website_url' => 'https://www.omantel.om',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f8/Omantel.svg',
        ],
        'One' => [
            'website_url' => 'https://www.one.hu/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/43/ONE2025_logo.png',
        ],
        'One Albania' => [
            'website_url' => 'https://www.one.al/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bf/One_%284iG%29_logo.svg',
        ],
        'One Communications Guyana' => [
            'website_url' => 'http://www.gtt.co.gy/',
        ],
        'One Montenegro' => [
            'website_url' => 'https://1.me/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bf/One_%284iG%29_logo.svg',
        ],
        'One NZ' => [
            'website_url' => 'https://one.nz',
        ],
        'Ooredoo' => [
            'website_url' => 'https://www.ooredoo.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a8/Ooredoo_logo.svg',
        ],
        'Ooredoo Maldives' => [
            'website_url' => 'https://www.ooredoo.mv/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a8/Ooredoo_logo.svg',
        ],
        'OPEN SIM i-mobile' => [
            'website_url' => 'http://www.cattelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/CAT_Logo.svg',
        ],
        'Optus' => [
            'website_url' => 'https://www.optus.com.au/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/ca/Optus_logo.svg',
        ],
        'Orange' => [
            'website_url' => 'https://www.orange.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c8/Orange_logo.svg',
        ],
        'Orange Jordan' => [
            'website_url' => 'http://www.orange.jo/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c8/Orange_logo.svg',
        ],
        'Orange Morocco' => [
            'website_url' => 'https://www.orange.ma/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c8/Orange_logo.svg',
        ],
        'Partner' => [
            'website_url' => 'https://www.partner.co.il/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/24/Partner_logo.svg',
        ],
        'Pelephone' => [
            'website_url' => 'https://www.pelephone.co.il',
        ],
        'Penguin' => [
            'website_url' => 'http://www.cattelecom.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/CAT_Logo.svg',
        ],
        'Personal' => [
            'website_url' => 'https://www.personal.com.ar/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c4/Personal_logonuevo.png',
        ],
        'Phoenix' => [
            'website_url' => 'https://днронлайн.рф',
        ],
        'Play' => [
            'website_url' => 'https://www.play.pl/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/11/Play_logo.svg',
        ],
        'Plus' => [
            'website_url' => 'https://www.plus.pl',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/24/Plus_Logo.svg',
        ],
        'POST' => [
            'website_url' => 'https://www.post.lu/',
        ],
        'Proximus' => [
            'website_url' => 'https://www.proximus.be/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/ff/Proximus_logo_2014.svg',
        ],
        'Rain' => [
            'website_url' => 'https://www.rain.co.za/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/eb/Rain_Logo.svg',
        ],
        'Rakuten Mobile' => [
            'website_url' => 'https://corp.mobile.rakuten.co.jp/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/62/Rakuten_Mobile_logo.svg',
        ],
        'redONE' => [
            'website_url' => 'http://www.tot.co.th',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/77/TOT_%28Thailand%29_logo.svg',
        ],
        'RighTel' => [
            'website_url' => 'http://www.rightel.ir',
        ],
        'Robi with Digital Sub Brand - Cirkle' => [
            'website_url' => 'http://www.robi.com.bd/',
        ],
        'Rocket Mobile' => [
            'website_url' => 'https://one.nz',
        ],
        'Rogers' => [
            'website_url' => 'https://www.rogers.com/mobility',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/78/Rogers_Communications_%282015%29.svg',
        ],
        'Roshan' => [
            'website_url' => 'http://www.roshan.af',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/76/Roshan_mobile.svg',
        ],
        'Rwandatel' => [
            'website_url' => 'http://www.rwandatel.rw//',
        ],
        'Sabafon' => [
            'website_url' => 'https://www.sabafon.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bf/Sabafon2001.png',
        ],
        'Safaricom' => [
            'website_url' => 'https://www.safaricom.co.ke',
        ],
        'Safaricom Telecommunications Ethiopia' => [
            'website_url' => 'https://safaricom.et/',
        ],
        'Salt' => [
            'website_url' => 'https://www.salt.ch/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/26/Salt_Logo_2015.svg',
        ],
        'SaskTel' => [
            'website_url' => 'https://www.sasktel.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/51/SaskTel_Logo.svg',
        ],
        'Saudi Telecom Company' => [
            'website_url' => 'https://www.stc.com.sa/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e3/STC-01.svg',
        ],
        'SetarNV' => [
            'website_url' => 'http://setar.aw/',
        ],
        'SFR' => [
            'website_url' => 'https://www.sfr.fr',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/97/SFR-2022-logo.svg',
        ],
        'Silknet' => [
            'website_url' => 'http://www.silk.ge',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e7/Silknet_Logo_2018.png',
        ],
        'SIMBA' => [
            'website_url' => 'https://simba.sg',
        ],
        'Singtel' => [
            'website_url' => 'https://www.singtel.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ee/Singtel_logo.svg',
        ],
        'SK Telecom' => [
            'website_url' => 'https://www.sktelecom.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2d/SK_Telecom_Logo.svg',
        ],
        'Skytel' => [
            'website_url' => 'http://www.skytel.mn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/28/SKYtel_Group.jpg',
        ],
        'SLTMobitel' => [
            'website_url' => 'http://www.slt.lk',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/SLTMobitel_Logo.svg',
        ],
        'Smart' => [
            'website_url' => 'https://smart-bz.com/',
        ],
        'Smart Communications' => [
            'website_url' => 'https://smart.com.ph/',
        ],
        'SmartCell' => [
            'website_url' => 'http://smarttel.com.np/',
        ],
        'SmarTone' => [
            'website_url' => 'https://www.smartone.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/64/SmarTone_logo.svg',
        ],
        'SoftBank Corp.' => [
            'website_url' => 'https://group.softbank/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/40/Softbank_mobile_logo.svg',
        ],
        'Somafone' => [
            'website_url' => 'http://www.somafone.com/index.htm',
        ],
        'Somtel' => [
            'website_url' => 'https://somtelnetwork.net/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/76/Somtel.jpg',
        ],
        'Spark' => [
            'website_url' => 'https://www.spark.co.nz/',
        ],
        'StarHub' => [
            'website_url' => 'https://www.starhub.com',
        ],
        'stc' => [
            'website_url' => 'https://www.stc.com.sa/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e3/STC-01.svg',
        ],
        'Sudani' => [
            'website_url' => 'http://www.sudatel.sd/',
        ],
        'Sunrise' => [
            'website_url' => 'https://www.sunrise.ch/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/10/Sunrise_2022.svg',
        ],
        'Sure' => [
            'website_url' => 'https://www.sure.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Sure_logo.png',
        ],
        'Swisscom' => [
            'website_url' => 'https://www.swisscom.ch/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f7/Swisscom_Logo.svg',
        ],
        'Swisscom FL' => [
            'website_url' => 'https://www.swisscom.ch/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f7/Swisscom_Logo.svg',
        ],
        'Syriatel' => [
            'website_url' => 'http://syriatel.sy',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/26/Logo_wordmark_Syriatel.png',
        ],
        'Síminn' => [
            'website_url' => 'https://www.siminn.is/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5a/Siminn_Logo.svg',
        ],
        'Sýn' => [
            'website_url' => 'https://syn.is/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/68/S%C3%BDn_2017_logo.svg',
        ],
        'T-2' => [
            'website_url' => 'http://www.t-2.net/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/Murska_Sobota_%2826%29_%285355063340%29_%28cropped%29.jpg',
        ],
        'T-Mobile' => [
            'website_url' => 'https://www.t-mobile.cz/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Telekom_Logo_2013.svg',
        ],
        't2' => [
            'website_url' => 'https://t2.ru',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/69/Tele2_logo.svg',
        ],
        'Taiwan Mobile' => [
            'website_url' => 'https://www.taiwanmobile.com/',
        ],
        'Tango' => [
            'website_url' => 'http://www.tango.lu/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/fc/Tango_%28telecom%29_logo.svg',
        ],
        'TDC' => [
            'website_url' => 'https://www.tdcgroup.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1e/Tdc.svg',
        ],
        'Team' => [
            'website_url' => 'https://telecomarmenia.am',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/bd/Telecom_Armenia.svg',
        ],
        'Telcel' => [
            'website_url' => 'http://www.telcel.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8e/Telcel_logo.svg',
        ],
        'Tele2' => [
            'website_url' => 'https://www.tele2.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/69/Tele2_logo.svg',
        ],
        'Telecel Ghana' => [
            'website_url' => 'https://telecel.com.gh',
        ],
        'Telekom' => [
            'website_url' => 'https://www.telekom.de/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Telekom_Logo_2013.svg',
        ],
        'Telekom GR' => [
            'website_url' => 'http://www.ote.gr',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/45/OTE_Logo.svg',
        ],
        'Telekom Romania' => [
            'website_url' => 'https://mobile.telekom.ro/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Telekom_Logo_2013.svg',
        ],
        'Telekom Slovenije' => [
            'website_url' => 'https://www.telekom.si/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/34/Telekom_Slovenije_Logo.svg',
        ],
        'Telemach' => [
            'website_url' => 'https://telemach.si/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Telemach_logo.svg',
        ],
        'Telenor' => [
            'website_url' => 'https://www.telenor.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/df/Telenor_Logo_%28symbol_and_wordmark%29.svg',
        ],
        'Telenor Pakistan' => [
            'website_url' => 'https://www.telenor.com.pk',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/df/Telenor_Logo_%28symbol_and_wordmark%29.svg',
        ],
        'Telesur' => [
            'website_url' => 'https://www.telesur.sr/',
        ],
        'Teletalk' => [
            'website_url' => 'http://www.teletalk.com.bd',
        ],
        'Telia' => [
            'website_url' => 'https://www.teliacompany.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7c/Telia_logo_2022.svg',
        ],
        'Telia Lietuva' => [
            'website_url' => 'https://www.omnitel.lt/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/4d/Omnitel_Lithuania_logo.gif',
        ],
        'Telkom' => [
            'website_url' => 'https://www.telkom.co.za',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e4/TelkomSA.svg',
        ],
        'Telkom Kenya' => [
            'website_url' => 'http://www.telkom.co.ke/',
        ],
        'Telkomcel' => [
            'website_url' => 'http://www.telkomcel.tl',
        ],
        'Telkomsel' => [
            'website_url' => 'http://www.telkomsel.co.id/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/04/Telkomsel_%282021%29.svg',
        ],
        'Telstra' => [
            'website_url' => 'https://www.telstra.com.au',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/4e/Telstra_logo_%28horizontal_variant%29.svg',
        ],
        'Telus' => [
            'website_url' => 'https://www.telus.com/en/mobility',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/63/Telus-Logo.svg',
        ],
        'Three' => [
            'website_url' => 'https://www.three.ie/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/40/Three_logo.svg',
        ],
        'Tigo' => [
            'website_url' => 'https://www.tigo.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/30/Logo_Millicom.png',
        ],
        'TIM' => [
            'website_url' => 'https://www.telecomitalia.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2c/Telecom_Italia_logo_%282016-present%29.svg',
        ],
        'TIM San Marino' => [
            'website_url' => 'https://www.tim.sm/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/02/TIM_logo_%282016-present%29.svg',
        ],
        'Timor Telecom' => [
            'website_url' => 'http://www.timortelecom.tp/',
        ],
        'TM' => [
            'website_url' => 'https://www.tmtambayan.ph',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7b/TM_Mobile_logo.svg',
        ],
        'TM CELL' => [
            'website_url' => 'https://www.tmcell.tm/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/49/Altyn-asyr.jpg',
        ],
        'TN Mobile' => [
            'website_url' => 'https://www.telecom.na/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f0/Telecom_Namibia_Logo.svg',
        ],
        'TNM' => [
            'website_url' => 'http://www.tnm.co.mw/',
        ],
        'TNT' => [
            'website_url' => 'https://tntph.com/',
        ],
        'touch' => [
            'website_url' => 'http://www.touch.com.lb/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/32/Logo_wordmark_Touch_%28Lebanon%29.png',
        ],
        'TracFone Wireless' => [
            'website_url' => 'http://www.tracfone.com/',
        ],
        'True' => [
            'website_url' => 'https://www.true.th/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1c/True_Corporation_%28Thailand%29.svg',
        ],
        'Tune Talk' => [
            'website_url' => 'http://www.tunetalk.com',
        ],
        'Tunisie Telecom' => [
            'website_url' => 'http://www.tunisietelecom.tn/',
        ],
        'Turkcell' => [
            'website_url' => 'https://www.turkcell.com.tr/',
        ],
        'tusass' => [
            'website_url' => 'https://www.tusass.gl/',
        ],
        'Türk Telekom' => [
            'website_url' => 'https://www.turktelekom.com.tr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/9f/T%C3%BCrk_Telekom_logo.svg',
        ],
        'U Mobile' => [
            'website_url' => 'https://www.u.com.my/',
        ],
        'Ucell' => [
            'website_url' => 'http://www.ucell.uz/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2c/Ucell_logo.png',
        ],
        'Ucom' => [
            'website_url' => 'https://www.ucom.am/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/78/Ucom_Armenia_Logo.jpg',
        ],
        'Ufone' => [
            'website_url' => 'http://ufone.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c0/Ufone_logo_%282023%29.png',
        ],
        'Umniah' => [
            'website_url' => 'http://www.umniah.com',
        ],
        'Unifi Mobile' => [
            'website_url' => 'https://unifi.com.my/personal/mobile',
        ],
        'Unitel' => [
            'website_url' => 'http://www.unitel.mn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/1b/Unitel_Logo.png',
        ],
        'UTel' => [
            'website_url' => 'http://www.utl.co.ug',
        ],
        'Uzmobile' => [
            'website_url' => 'http://uztelecom.uz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e4/Uztelecom-primary-logotype-RGB.svg',
        ],
        'Vainah Telecom' => [
            'website_url' => 'http://chechnya.gov.ru/',
        ],
        'Vala' => [
            'website_url' => 'https://kosovotelecom.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2d/Vala_%28Unternehmen%29_logo.svg',
        ],
        'Verizon' => [
            'website_url' => 'https://www.yourwirelessinc.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/83/Verizon_2024.svg',
        ],
        'Vi' => [
            'website_url' => 'https://www.vodafoneidea.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e3/Vodafone_Idea_logo.png',
        ],
        'Vidéotron' => [
            'website_url' => 'https://www.videotron.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/8c/Vid%C3%A9otron_2017_logo.png',
        ],
        'Vietnamobile' => [
            'website_url' => 'http://vietnamobile.com.vn/',
        ],
        'Viettel' => [
            'website_url' => 'https://viettel.vn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/d/d5/Viettel_Group_en_logo.svg',
        ],
        'Vinaphone' => [
            'website_url' => 'http://www.vinaphone.com.vn/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ed/Logo_Vinaphone.svg',
        ],
        'Virgin Mobile' => [
            'website_url' => 'http://www.virginmobile.cl/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/05/Virgin_Mobile_Chile.svg',
        ],
        'Virgin mobile KSA' => [
            'website_url' => 'https://virginmobile.sa/',
        ],
        'Viva' => [
            'website_url' => 'http://www.viva.com.bo/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/ba/VIVA2020.png',
        ],
        'Vivacom' => [
            'website_url' => 'https://www.vivacom.bg',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/e5/Vivacom_logo_2021.svg',
        ],
        'VIVIFI' => [
            'website_url' => 'https://www.singtel.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ee/Singtel_logo.svg',
        ],
        'Vivo' => [
            'website_url' => 'https://www.vivo.com.br',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/f/f3/Vivo_%28Brazil%29_logo.svg',
        ],
        'Vodacom' => [
            'website_url' => 'https://www.vodacom.com',
        ],
        'Vodafone' => [
            'website_url' => 'https://www.vodafone.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Vodafone_logo_2017.svg',
        ],
        'Vodafone Albania' => [
            'website_url' => 'https://www.vodafone.al/',
        ],
        'Vodafone AU' => [
            'website_url' => 'https://www.vodafone.com.au/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5a/Vodafone_logo_1997.png',
        ],
        'Vodafone Egypt' => [
            'website_url' => 'https://vodafone.com.eg/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Vodafone_logo_2017.svg',
        ],
        'Vodafone Germany' => [
            'website_url' => 'https://www.vodafone.de/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Vodafone_logo_2017.svg',
        ],
        'Vodafone Turkey' => [
            'website_url' => 'http://www.vodafone.com.tr/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5f/Vodafone_logo_2017.svg',
        ],
        'VTR Móvil' => [
            'website_url' => 'http://www.vtr.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0d/VTRlogo.png',
        ],
        'Warehouse Mobile' => [
            'website_url' => 'https://www.2degrees.nz',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/2d_logo.svg',
        ],
        'Warid' => [
            'website_url' => 'http://www.waridtel.com',
        ],
        'We' => [
            'website_url' => 'https://te.eg',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/We_logo.svg',
        ],
        'wecom' => [
            'website_url' => 'https://xphone.co.il/',
        ],
        'Wind Tre' => [
            'website_url' => 'https://www.windtre.it/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2c/Wind_Tre_logo.svg',
        ],
        'WOM' => [
            'website_url' => 'https://www.wom.cl/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/3/33/WOM_Chile_logo.svg',
        ],
        'XLSMART' => [
            'website_url' => 'https://www.xlsmart.co.id',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/62/XLSmart.svg',
        ],
        'Yemen Mobile' => [
            'website_url' => 'https://www.yemenmobile.com.ye/',
        ],
        'Yes' => [
            'website_url' => 'https://www.yes.my/',
        ],
        'Yettel' => [
            'website_url' => 'https://www.yettel.bg/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6b/Yettellimelogo.svg',
        ],
        'YOU' => [
            'website_url' => 'https://www.mtn.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/af/MTN_Logo.svg',
        ],
        'Zain' => [
            'website_url' => 'https://www.zain.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b1/Zain_%28Unternehmen%29_logo.svg',
        ],
        'Zain Jordan' => [
            'website_url' => 'https://zain.com',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b1/Zain_%28Unternehmen%29_logo.svg',
        ],
        'Zamtel' => [
            'website_url' => 'http://www.zamtel.zm/',
        ],
        'Zero1' => [
            'website_url' => 'https://www.singtel.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ee/Singtel_logo.svg',
        ],
        'Zong' => [
            'website_url' => 'http://www.zong.com.pk',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/43/Zong_Logo.png',
        ],
        'ZYM Mobile' => [
            'website_url' => 'https://www.singtel.com/',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/e/ee/Singtel_logo.svg',
        ],
        'Ålcom' => [
            'website_url' => 'https://www.alcom.ax',
            'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/ba/%C3%85lcom_logo.svg',
        ],
    ];
};
