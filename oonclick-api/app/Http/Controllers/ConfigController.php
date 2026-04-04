<?php

namespace App\Http\Controllers;

use App\Models\AudienceCriterion;
use App\Models\CampaignFormat;
use App\Models\FeatureSetting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function campaignFormats(): JsonResponse
    {
        $formats = CampaignFormat::getActiveFormats();
        return response()->json(['data' => $formats]);
    }

    public function audienceCriteria(): JsonResponse
    {
        $criteria = AudienceCriterion::getActiveCriteria();
        return response()->json(['data' => $criteria]);
    }

    public function features(): JsonResponse
    {
        $features = FeatureSetting::getEnabled();
        return response()->json(['data' => $features->map(fn ($f) => [
            'slug'   => $f->feature_slug,
            'label'  => $f->label,
            'config' => $f->config,
        ])]);
    }
}
