<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LanguagePhrase;
use App\Support\Phrases\PhraseCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wichtige Saetze fuer Reisende in einer Landessprache (ISO-639-Code wie in countries.languages):
 *
 *   GET /v1/phrases/{language}?lang=de   – nach Gruppen, mit Ausgangstext in lang (de, en, nl) und ggf. Umschrift
 */
class PhraseController extends Controller
{
    public function show(Request $request, string $language): JsonResponse
    {
        $request->validate(['lang' => 'nullable|string|max:5']);
        $lang = in_array($request->query('lang'), ['de', 'en', 'nl'], true) ? $request->query('lang') : 'de';
        $language = strtolower($language);

        $rows = LanguagePhrase::where('language', $language)->get()->keyBy('key');
        if ($rows->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No phrases for this language.'], 404);
        }

        $categories = collect(PhraseCatalog::CATEGORIES)->map(fn ($names, $category) => [
            'key' => $category,
            'name' => $names[$lang],
            'phrases' => collect(PhraseCatalog::PHRASES)
                ->filter(fn ($p, $key) => $p[0] === $category && $rows->has($key))
                ->map(fn ($p, $key) => [
                    'key' => $key,
                    'source' => PhraseCatalog::source($key, $lang),
                    'text' => $rows[$key]->text,
                    'romanization' => $rows[$key]->romanization,
                ])->values()->all(),
        ])->filter(fn ($c) => $c['phrases'] !== [])->values()->all();

        return response()->json(['success' => true, 'data' => [
            'language' => $language,
            'name' => class_exists(\Locale::class) ? \Locale::getDisplayLanguage($language, $lang) : $language,
            'status' => $rows->contains(fn ($p) => $p->status === LanguagePhrase::STATUS_AI_DRAFT) ? LanguagePhrase::STATUS_AI_DRAFT : LanguagePhrase::STATUS_REVIEWED,
            'categories' => $categories,
        ]]);
    }
}
