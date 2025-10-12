<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:debug
                            {--task= : Run a specific scheduled task immediately}
                            {--list : List all scheduled tasks with their next run time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug Laravel scheduler - list tasks or run specific task immediately';

    /**
     * Execute the console command.
     */
    public function handle(Schedule $schedule): int
    {
        if ($this->option('list')) {
            return $this->listScheduledTasks($schedule);
        }

        if ($taskName = $this->option('task')) {
            return $this->runSpecificTask($schedule, $taskName);
        }

        // デフォルト：全タスクをリスト表示
        return $this->listScheduledTasks($schedule);
    }

    /**
     * List all scheduled tasks with details
     */
    private function listScheduledTasks(Schedule $schedule): int
    {
        $events = $schedule->events();

        if (empty($events)) {
            $this->warn('No scheduled tasks found.');
            return Command::SUCCESS;
        }

        $this->info('📅 Scheduled Tasks (' . count($events) . ' total)');
        $this->newLine();

        $tableData = [];
        foreach ($events as $index => $event) {
            $command = $this->getEventCommand($event);
            $expression = $event->expression;
            $description = $event->description ?? 'No description';
            $timezone = $event->timezone ?? config('app.timezone');

            // 次回実行時刻を計算
            $nextRunDate = \Carbon\Carbon::now($timezone);
            try {
                $cron = new \Cron\CronExpression($expression);
                $nextRunDate = \Carbon\Carbon::instance($cron->getNextRunDate());
            } catch (\Exception $e) {
                $nextRunDate = 'Invalid cron expression';
            }

            $tableData[] = [
                'ID' => $index + 1,
                'Command' => $this->truncate($command, 50),
                'Schedule' => $expression,
                'Next Run' => $nextRunDate instanceof \Carbon\Carbon
                    ? $nextRunDate->format('Y-m-d H:i:s')
                    : $nextRunDate,
                'Timezone' => $timezone,
            ];
        }

        $this->table(
            ['ID', 'Command', 'Schedule', 'Next Run', 'Timezone'],
            $tableData
        );

        $this->newLine();
        $this->info('💡 Tips:');
        $this->line('  • Run specific task: php artisan schedule:debug --task=<ID>');
        $this->line('  • View all tasks: php artisan schedule:list');
        $this->line('  • Test scheduler: php artisan schedule:run');

        return Command::SUCCESS;
    }

    /**
     * Run a specific scheduled task immediately
     */
    private function runSpecificTask(Schedule $schedule, string $taskId): int
    {
        $events = $schedule->events();

        // IDが数値の場合（1から始まる）
        if (is_numeric($taskId)) {
            $index = (int) $taskId - 1;
            if (!isset($events[$index])) {
                $this->error("Task ID {$taskId} not found.");
                return Command::FAILURE;
            }
            $event = $events[$index];
        } else {
            // コマンド名で検索
            $event = collect($events)->first(function ($event) use ($taskId) {
                $command = $this->getEventCommand($event);
                return str_contains($command, $taskId);
            });

            if (!$event) {
                $this->error("Task containing '{$taskId}' not found.");
                return Command::FAILURE;
            }
        }

        $command = $this->getEventCommand($event);
        $this->info("🚀 Running task: {$command}");
        $this->newLine();

        try {
            // タスクを強制実行
            $event->run($this->laravel);
            $this->newLine();
            $this->info('✅ Task completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Task failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Get the command string from an event
     */
    private function getEventCommand($event): string
    {
        if (isset($event->command)) {
            return $event->command;
        }

        if (isset($event->description)) {
            return $event->description;
        }

        return 'Closure';
    }

    /**
     * Truncate a string to a specific length
     */
    private function truncate(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length - 3) . '...';
    }
}
