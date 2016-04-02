<?php

namespace App\Process\Router;

use App\Action\Chat\ChatAction;
use App\Action\Index\IndexAction;
use App\Action\Resource\ResourceAction;
use Surume\Channel\ChannelBaseInterface;
use Surume\Channel\Extra\Request;
use Surume\Channel\Router\RuleHandler;
use Surume\Core\CoreInterface;
use Surume\Ipc\Socket\SocketListener;
use Surume\Promise\Promise;
use Surume\Promise\PromiseInterface;
use Surume\Runtime\Container\ProcessContainer;
use Surume\Runtime\RuntimeInterface;
use Surume\Throwable\Exception\Runtime\ExecutionException;
use Surume\Transfer\IoServer;
use Surume\Transfer\Websocket\WsServer;
use SplQueue;

class RouterController extends ProcessContainer implements RuntimeInterface
{
    /**
     * @var SplQueue
     */
    private $tasks;

    /**
     * @var bool[]
     */
    private $workers;

    /**
     * @var int
     */
    private $workersIndex;

    /**
     * @var ChannelBaseInterface
     */
    private $channel;

    /**
     * @param $data
     * @return PromiseInterface
     */
    public function assignWork($data)
    {
        $this->queueRequest($data);
        return $this->requestWorker();
    }

    /**
     * @param CoreInterface $core
     * @return array
     */
    protected function config(CoreInterface $core)
    {
        return [
            'channel.channels.master.config.endpoint' => 'tcp://%host.main%:2080',
            'channel.channels.slave.config.endpoint'  => 'tcp://%host.main%:2081'
        ];
    }

    /**
     * @param CoreInterface $core
     * @return RuntimeInterface
     */
    protected function construct(CoreInterface $core)
    {
        $this->tasks = new SplQueue();
        $this->workers = [];
        $this->workersIndex = 0;

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

        $loop->addPeriodicTimer(1, function() {
            echo 'Process::[' . $this->alias() . '] is alive and ' . time() . PHP_EOL;
        });

        $container = $this;
        $promise = new Promise();
        $promise
            ->then(
                function() use($container) {
                    return $container->createRouterWorkerConnection();
                }
            )
            ->then(
                function() use($container) {
                    return $container->createWorkerPool();
                }
            )
            ->then(
                function() use($container) {
                    return $container->createIoServer();
                }
            )
            ->done(
                function() {
                    echo 'Router is up!' . PHP_EOL;
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
    private function createIoServer()
    {
        $core = $this->core();
        $loop = $core->make('Loop');

        $io = new IoServer(
            $socket = new SocketListener('tcp://127.0.0.1:4080', $loop)
        );

        $io->addRoute('/', new IndexAction($this));
        $io->addRoute('/chat', new WsServer(new ChatAction($this)));
        $io->addRoute('/{resourceType}/{resourceName}', new ResourceAction($this));
    }

    /**
     * @return PromiseInterface
     */
    private function createWorkerPool()
    {
        $container = $this;
        $manager  = $this->manager();
        $promises = [];
        for ($i=1; $i<=3; $i++)
        {
            $alias = 'Worker_' . $i;
            $class = 'Worker';

            $promises[] = $manager
                ->createThread($alias, $class)
                ->then(
                    function($value) use($container, $alias) {
                        $container->registerWorker($alias);
                        return $value;
                    }
                );
            ;
        }
        return Promise::all($promises);
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
                'class' => 'Surume\Channel\Model\Zmq\ZmqRouter',
                'config' => [
                    'hosts'     => $runtime->alias(),
                    'type'      => 1,
                    'endpoint'  => 'tcp://127.0.0.1:2082'
                ]
            ]
        );

        $router = $channel->input();
        $router->addAnchor(
            new RuleHandler(function($params) {
                // DO NOTHING
            })
        );

        $router = $channel->output();
        $router->addAnchor(
            function($receiver, $protocol, $flags, callable $success = null, callable $failure = null, callable $cancel = null, $timeout = 0.0) use($channel) {
                $channel->push($receiver, $protocol, $flags, $success, $failure, $cancel, $timeout);
            }
        );

        $channel->start();

        $this->channel = $channel;

        return null;
    }

    /**
     * @param string $action
     */
    private function queueRequest($action)
    {
        $this->tasks->enqueue($action);
    }

    /**
     * @param string $alias
     */
    private function registerWorker($alias)
    {
        $this->workers[] = $alias;
    }

    /**
     * @return PromiseInterface
     */
    private function requestWorker()
    {
        if (($task = $this->tasks->dequeue()) !== null)
        {
            return (new Request(
                $this->channel,
                $this->getNextWorker(),
                $task
            ))->call();
        }

        return (new Promise())
            ->reject(
                new ExecutionException('There is nothing on tasks queue.')
            );
    }

    /**
     * @return string
     */
    private function getNextWorker()
    {
        if ($this->workersIndex >= count($this->workers))
        {
            $this->workersIndex = 0;
        }

        return $this->workers[$this->workersIndex++];
    }
}
