<?php

/**
 * Recruitment pipeline + AI candidate evaluation.
 *
 * Nothing secret lives here: the API key comes from the environment (.env), never from the code.
 *
 *   RECRUIT_AI_PROVIDER   auto (default) | anthropic | local
 *                         auto      = use Anthropic when ANTHROPIC_API_KEY is set, otherwise the built-in rules engine
 *                         anthropic = always use the Anthropic API (falls back to the rules engine if a call fails)
 *                         local     = never call an external service (candidate data stays on this server)
 *   ANTHROPIC_API_KEY     your key (leave empty to stay on the built-in rules engine)
 *   RECRUIT_AI_MODEL      model id, default claude-sonnet-5-5
 *   ANTHROPIC_API_URL     override only for a proxy / gateway
 */
return [
    'ai' => [
        'provider' => env('RECRUIT_AI_PROVIDER', 'auto'),
        'anthropic' => [
            'key'     => env('ANTHROPIC_API_KEY'),
            'model'   => env('RECRUIT_AI_MODEL', 'claude-sonnet-5-5'),
            'url'     => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages'),
            'version' => '2023-06-01',
            'timeout' => 60,
        ],
    ],

    // pipeline: stored value => label shown to people
    'statuses' => [
        'applied_active' => 'Applied',
        'review'         => 'Under review',
        'interview'      => 'Interview',
        'approved'       => 'Approved',
        'reject'         => 'Rejected',
    ],

    // where a candidate may move from each status
    'transitions' => [
        'applied_active' => ['review', 'interview', 'reject'],
        'review'         => ['applied_active', 'interview', 'approved', 'reject'],
        'interview'      => ['review', 'approved', 'reject'],
        'approved'       => ['review', 'interview', 'applied_active'],
        'reject'         => ['applied_active', 'review'],
    ],

    'default_weights' => ['education' => 15, 'experience' => 25, 'skills' => 30, 'answers' => 20, 'competencies' => 10],
    'default_thresholds' => ['strong' => 85, 'qualified' => 70, 'review' => 50],

    'recommendations' => [
        'strong'    => 'Strong match – interview recommended',
        'qualified' => 'Qualified – recruiter review',
        'review'    => 'Needs further review',
        'low'       => 'Low match – manual review required',
    ],
];
