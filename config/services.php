<?php

return [

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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'krea/krea-2-medium'),
        'edit_model' => env('OPENAI_IMAGE_EDIT_MODEL', 'google/gemini-3.1-flash-lite-image'),
        'vision_model' => env('OPENAI_VISION_MODEL'),
        'vision_models' => env('OPENAI_VISION_MODEL')
            ? [env('OPENAI_VISION_MODEL')]
            : [
                'google/gemma-4-31b-it:free',
                'google/gemma-4-26b-a4b-it:free',
                'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
                'nvidia/nemotron-nano-12b-v2-vl:free',
            ],
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];