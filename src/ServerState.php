<?php

namespace Ody\Swoole;

class ServerState
{
    /**
     * @var ServerState|null
     */
    protected static ?self $instance = null;

    /**
     * @var string
     */
    protected readonly string $path;

    public function __construct(){
        $this->path = storagePath('serverState.json');
    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        return self::$instance = new self();
    }

    /**
     * @return array
     */
    public function getInformation(): array
    {
        $data = is_readable($this->path)
            ? json_decode(file_get_contents($this->path), true)
            : [];

        return [
            'masterProcessId' => $data['pIds']['masterProcessId'] ?? null ,
            'managerProcessId' => $data['pIds']['managerProcessId'] ?? null ,
            'watcherProcessId' => $data['pIds']['watcherProcessId'] ?? null ,
//            'factoryProcessId' => $data['pIds']['factoryProcessId'] ?? null ,
//            'queueProcessId' => $data['pIds']['queueProcessId'] ?? null ,
//            'schedulingProcessId' => $data['pIds']['schedulingProcessId'] ?? null ,
            'workerProcessIds' => $data['pIds']['workerProcessIds'] ?? [] ,
            'websocketMasterProcessId' => $data['pIds']['websocketMasterProcessId'] ?? null ,
            'websocketManagerProcessId' => $data['pIds']['websocketManagerProcessId'] ?? null ,
            'websocketWorkerProcessIds' => $data['pIds']['websocketWorkerProcessIds'] ?? [] ,
        ];
    }

    /**
     * @psalm-api
     */
    public function setManagerProcessId(?int $id): void
    {
        $this->setId('managerProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWebsocketManagerProcessId(?int $id): void
    {
        $this->setId('websocketManagerProcessId', $id);
    }

    /**
     * @psalm-api
     */

    public function setMasterProcessId(?int $id): void
    {
        $this->setId('masterProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWebsocketMasterProcessId(?int $id): void
    {
        $this->setId('websocketMasterProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWatcherProcessId(?int $id): void
    {
        $this->setId('watcherProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setFactoryProcessId(int $id): void
    {
        $this->setId('factoryProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setQueueProcessId(int $id): void
    {
        $this->setId('queueProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setSchedulingProcessId(int $id): void
    {
        $this->setId('schedulingProcessId', $id);
    }

    /**
     * @psalm-api
     */
    public function setWorkerProcessIds(array $ids): void
    {
        $this->setId('workerProcessIds', $ids);
    }

    /**
     * @psalm-api
     */
    public function setWebsocketWorkerProcessIds(array $ids): void
    {
        $this->setId('websocketWorkerProcessIds', $ids);
    }

    /**
     * @psalm-api
     */
    public function getWebsocketManagerProcessId(): int|null
    {
        return $this->getInformation()['websocketManagerProcessId'];
    }


    /**
     * @psalm-api
     */
    public function getManagerProcessId(): int|null
    {
        return $this->getInformation()['managerProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getWebsocketMasterProcessId(): int|null
    {
        return $this->getInformation()['websocketMasterProcessId'];
    }


    /**
     * @psalm-api
     */
    public function getMasterProcessId(): int|null
    {
        return $this->getInformation()['masterProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getWatcherProcessId(): int|null
    {
        return $this->getInformation()['watcherProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getFactoryProcessId(): int|null
    {
        return $this->getInformation()['factoryProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getQueueProcessId(): int|null
    {
        return $this->getInformation()['queueProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getSchedulingProcessId(): int|null
    {
        return $this->getInformation()['schedulingProcessId'];
    }

    /**
     * @psalm-api
     */
    public function getWorkerProcessIds(): array
    {
        return $this->getInformation()['workerProcessIds'];
    }

    /**
     * @psalm-api
     */
    public function getWebsocketWorkerProcessIds(): array
    {
        return $this->getInformation()['workerProcessIds'];
    }

    /**
     * @psalm-api
     */
    protected function setId(string $key, int|array|null $id): void
    {
        file_put_contents($this->path, json_encode(
            [
                'pIds' => array_merge($this->getInformation(), [$key => $id])
            ],
            JSON_PRETTY_PRINT
        ));
    }

    public function clearHttpProcessIds(): void
    {
        $this->setWorkerProcessIds([]);
        $this->setMasterProcessId(null);
        $this->setManagerProcessId(null);
        $this->setWatcherProcessId(null);
    }

    public function clearWebsocketProcessIds(): void
    {
        $this->setWebsocketWorkerProcessIds([]);
        $this->setWebsocketMasterProcessId(null);
        $this->setWebsocketManagerProcessId(null);
        $this->setWatcherProcessId(null);
    }

    public function httpServerIsRunning(): bool
    {
        $managerProcessId = $this->getManagerProcessId();
        $masterProcessId = $this->getMasterProcessId();
        if (
            !is_null($managerProcessId) &&
            !is_null($masterProcessId)
        ){
            return (
                posix_kill($managerProcessId, SIG_DFL) &&
                posix_kill($masterProcessId, SIG_DFL)
            );
        }

        return false;
    }

    public function websocketServerIsRunning(): bool
    {
        $websocketManagerProcessId = $this->getWebsocketManagerProcessId();
        $websocketMasterProcessId = $this->getWebsocketMasterProcessId();
        if (
            !is_null($websocketManagerProcessId) &&
            !is_null($websocketMasterProcessId)
        ){
            return (
                posix_kill($websocketManagerProcessId, SIG_DFL) &&
                posix_kill($websocketMasterProcessId, SIG_DFL)
            );
        }

        return false;
    }

    /**
     * @param array $processIds
     * @return void
     */
    public function killProcesses(array $processIds): void
    {
        foreach ($processIds as $processId) {
            if (!is_null($processId) && posix_kill($processId, SIG_DFL)){
                posix_kill($processId, SIGTERM);
            }
        }
    }
}