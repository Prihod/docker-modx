<?php

namespace App\Runner;

use App\Tasks\Task;
use App\Traits\InitializeTrait;
use App\Traits\PropertiesTrait;
use App\Utils\Logger;

class Runner implements RunnerInterface
{
    use PropertiesTrait;
    use InitializeTrait;

    protected array $tasks = [];

    public function __construct(\modX $modx, array $properties = [])
    {
        $this->initialize($modx, $properties);
    }

    public function run(): void
    {
        $this->loadTasks();
        if (empty($this->tasks)) {
            return;
        }
        Logger::info("Start configurator Modx");
        foreach ($this->tasks as $task) {
            $this->runTask($task);
            $this->modx->reloadConfig();
        }
        Logger::info("Finish configurator Modx");
    }

    protected function loadTasks(): array
    {
        $handlers = $this->getProperty('tasks', []);

        foreach ($handlers as $handler) {
            $this->addTask($handler);
        }

        return $this->tasks;
    }

    protected function addTask(string $handler): void
    {
        try {
            $fullClass = 'App\\Tasks\\' . $handler;
            if (!class_exists($fullClass)) {
                Logger::error("Class '{$fullClass}' not found.");
                return;
            }
            $task = new $fullClass($this->modx, $this->getProperties());
            if (!$task instanceof Task) {
                Logger::error("Task handler error: The handler '{$handler}' must be an instance of Task.");
                return;
            }

            $this->tasks[] = $task;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
    }

    protected function runTask(Task $task): void
    {
        try {
            Logger::info("Start execute task: '{$task->getName()}'");
            $task->execute();
            Logger::info("Finish execute task: '{$task->getName()}'");
        } catch (\Exception $e) {
            Logger::error("Error in task '{$task->getName()}': " . $e->getMessage());
        }
    }
}
