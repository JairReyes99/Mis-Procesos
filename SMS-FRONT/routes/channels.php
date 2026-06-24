<?php

use App\Models\Campaign;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado por campaña — solo usuarios de la misma empresa
Broadcast::channel('campaign.{id}', function ($user, $id) {
    $campaign = Campaign::find($id);
    return $campaign && (int) $user->company_id === (int) $campaign->company_id;
});

// Canal privado por usuario — SMS de prueba
Broadcast::channel('test-sms.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal privado por empresa — balance en tiempo real
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    if ($user->company_id) {
        return (int) $user->company_id === (int) $companyId;
    }
    // Super-admin: verify the company exists before granting access
    return \App\Models\Company::where('id', $companyId)->exists();
});
