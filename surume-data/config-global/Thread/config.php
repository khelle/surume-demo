<?php

return [
    'channel' => [
        'models'   => [],
        'plugins'  => [],
        'channels' => [
            'master' => [
                'class' => 'Surume\Channel\Model\Zmq\ZmqDealer',
                'config' => [
                    'type'      => 2,
                    'endpoint'  => 'tcp://%host.main%:2080'
                ]
            ],
            'slave' => [
                'class' => 'Surume\Channel\Model\Zmq\ZmqRouter',
                'config' => [
                    'type'      => 1,
                    'endpoint'  => 'tcp://%host.main%:2081'
                ]
            ],
            'console' => [
                'class'  => 'Surume\Channel\Model\Zmq\ZmqRouter',
                'config' => [
                    'type'      => 1,
                    'endpoint'  => 'tcp://%host.main%:2061'
                ]
            ]
        ]
    ],
    'command' => [
        'models'   => [],
        'plugins'  => [],
        'commands' => []
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
        ],
        'tolerance' => [
            'parent.keepalive' => 15.0,
            'child.keepalive'  => 15.0
        ]
    ],
    'error' => [
        'handlers' => [],
        'plugins'  => [],
        'manager'  => [
            'params' => [
                'timeout'         => 4.0,
                'retriesLimit'    => 10,
                'retriesInterval' => 2.0
            ],
            'handlers' => [],
            'plugins'  => []
        ],
        'supervisor' => [
            'params' => [
                'timeout'         => 4.0,
                'retriesLimit'    => 10,
                'retriesInterval' => 2.0
            ],
            'handlers' => [],
            'plugins'  => []
        ]
    ],
    'filesystem' => [
        'cloud' => []
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
    ],
    'runtime' => [
        'manager' => [
            'process' => [
                'class'  => 'Surume\Runtime\Container\Manager\ProcessManagerNull',
                'config' => []
            ],
            'thread' => [
                'class'  => 'Surume\Runtime\Container\Manager\ThreadManagerBase',
                'config' => []
            ]
        ]
    ]
];
