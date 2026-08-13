<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'moviebox' => [
        'secret_key' => env('MOVIEBOX_SECRET_KEY', '76iRl07s0xSN9jqmEWAt79EBJZulIQIsV64FZr2O'),
        'hosts' => [
            'https://api6.aoneroom.com',
            'https://api5.aoneroom.com',
            'https://api4.aoneroom.com',
            'https://api4sg.aoneroom.com',
            'https://api3.aoneroom.com',
            'https://api6sg.aoneroom.com',
            'https://api.inmoviebox.com',
        ],
    ],

    'nvidia' => [
        'api_key' => env('NVIDIA_API_KEY', ''),
        'base_url' => env('NVIDIA_API_URL', 'https://integrate.api.nvidia.com/v1'),
        'llm_model' => env('NVIDIA_LLM_MODEL', 'meta/llama-3.1-8b-instruct'),
        'embedding_model' => env('NVIDIA_EMBEDDING_MODEL', 'nvidia/nv-embed-v2'),
    ],

    'anichin' => [
        'api_url'      => env('ANICHIN_API_URL', 'https://api.anichin.bio'),
        'priv_api_url' => env('ANICHIN_PRIV_API_URL', 'https://priv-api.anichin.bio'),
        'api_key'      => env('ANICHIN_API_KEY', 'ANICHIN-285757D6C7247E91356ACD175840B15D'),
        'priv_api_key' => env('ANICHIN_PRIV_API_KEY', 'dk_live_d6350c820e0098a55f8d1e88c7c255c5'),
    ],

    // ─── Social OAuth Providers ───────────────────────────────────────────────

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

];
