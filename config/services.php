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
        'chat_models' => env('OPENAI_CHAT_MODELS')
            ? array_filter(array_map('trim', explode(',', env('OPENAI_CHAT_MODELS'))))
            : [
                'liquid/lfm-2.5-2.6b:free',
                'dots-studio/dots-3-note-preview:free',
                'nex-agi/nex-n2.5-mini:free',
                'nvidia/nemotron-3.5-lightning:free',
                'nex-agi/nex-n2.5-pro:free',
                'inclusionai/ling-3.0-flash-vl:free',
            ],
        'vision_model' => env('OPENAI_VISION_MODEL'),
        'vision_models' => env('OPENAI_VISION_MODELS')
            ? array_filter(array_map('trim', explode(',', env('OPENAI_VISION_MODELS'))))
            : (env('OPENAI_VISION_MODEL')
                ? [env('OPENAI_VISION_MODEL')]
                : [
                    'inclusionai/ling-3.0-flash-vl:free',
                    'google/gemma-4-31b-it:free',
                    'google/gemma-4-26b-a4b-it:free',
                    'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
                ]),
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