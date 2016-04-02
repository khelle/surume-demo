<?php

return [
    'channel' => [
        'models'   => [],
        'plugins'  => [],
        'channels' => [
            'console' => [
                'class'  => 'Surume\Channel\Model\Zmq\ZmqDealer',
                'config' => [
                    'type'      => 2,
                    'endpoint'  => 'tcp://%host.main%:2060'
                ]
            ]
        ]
    ],
    'command' => [
        'models'   => [],
        'plugins'  => []
    ],
    'config' => [
        'mode' => 'merge', // replace||merge||isolate
        'dirs' => []
    ],
    'core' => [
        'project' => [
            'main.alias' => 'Root',
            'main.name'  => 'Router',
        ],
        'cli' => [
            'title' => 'php'
        ],
        'ini'  => [
            'memory_limit' => '512M'
        ]
    ],
    'log' => [
        'messagePattern' => "[%datetime% %level_name%.%channel%]%message%\n\n",
        'datePattern'    => "Y-m-d H:i:s",
        'filePattern'    => "%datapath%/log/%level%/surume.%date%.log",
        'fileLocking'    => false,
        'filePermission' => 0755
    ],
    'loop' => [
        'model' => 'Surume\Loop\Model\StreamSelectLoop'
    ]
];
