<?php

namespace App\Http\Controllers;

use App\Models\SocialLink;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function index(Request $request)
    {
        $query = SocialLink::query()->where('is_active', true);
        if ($platform = $request->get('platform')) {
            $query->where('platform', $platform);
        }
        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('title','like',"%$search%")
                  ->orWhere('description','like',"%$search%")
                  ->orWhere('city','like',"%$search%")
                  ->orWhere('country','like',"%$search%");
            });
        }
        $links = $query->orderByDesc('id')->limit(500)->get();
        return response()->json(['data' => $links]);
    }
}


