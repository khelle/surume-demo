<?php

namespace App\Thread\Worker;

use Surume\Channel\Channel;
use Surume\Channel\Extra\Response;
use Surume\Channel\Router\RuleHandler;
use Surume\Core\CoreInterface;
use Surume\Promise\Promise;
use Surume\Runtime\Container\ProcessContainer;
use Surume\Runtime\RuntimeInterface;

class WorkerController extends ProcessContainer implements RuntimeInterface
{
    /**
     * @param CoreInterface $core
     * @return array
     */
    protected function config(CoreInterface $core)
    {
        return [
            'channel.channels.master.config.endpoint' => 'tcp://%host.main%:2081',
            'channel.channels.slave.config.endpoint'  => 'ipc://%alias%'
        ];
    }

    /**
     * @param CoreInterface $core
     * @return RuntimeInterface
     */
    protected function construct(CoreInterface $core)
    {
        $this->onCreate(function() {
            $this->onCreateHandler();
        });

        return $this;
    }

    /**
     *
     */
    private function onCreateHandler()
    {
        $core = $this->core();
        $loop = $core->make('Loop');

        $container = $this;
        $promise = new Promise();
        $promise
            ->then(
                function() use($container) {
                    $container->createRouterWorkerConnection();
                }
            )
            ->done(
                function() {
                    echo 'Worker is up!' . PHP_EOL;
                },
                function($ex) {
                    echo (string) $ex . PHP_EOL;
                }
            )
        ;

        $promise->resolve();
    }

    /**
     *
     */
    private function createRouterWorkerConnection()
    {
        $runtime = $this;
        $core = $this->core();

        $factory = $core->make('Surume\Channel\ChannelFactoryInterface');

        $channel = $factory->create(
            'Surume\Channel\ChannelBase',
            [
                'class'  => 'Surume\Channel\Model\Zmq\ZmqDealer',
                'config' => [
                    'hosts'     => $runtime->parent(),
                    'type'      => 2,
                    'endpoint'  => 'tcp://127.0.0.1:2082'
                ]
            ]
        );

        $router = $channel->input();
        $router->addAnchor(
            new RuleHandler(function($params) use($channel) {
                $protocol = $params['protocol'];

                $answer = $this->parseRequest($protocol->getMessage());

                if ($protocol->getType() === Channel::TYPE_REQ)
                {
                    return (new Response($channel, $protocol, $answer))->call();
                }
            })
        );

        $router = $channel->output();
        $router->addAnchor(
            function($receiver, $protocol, $flags, callable $success = null, callable $failure = null, callable $cancel = null, $timeout = 0.0) use($channel) {
                $channel->push($receiver, $protocol, $flags, $success, $failure, $cancel, $timeout);
            }
        );

        $channel->start();
    }

    /**
     * @var string $request
     * @return string
     */
    private function parseRequest($request)
    {
        $filePath = $this->core()->basePath() . '/public';
        $page = $request;

        return file_get_contents($filePath . '/' . $page . '.html');
    }
}
