<?php

namespace App\Console;

use App\Models\Microtask;
use App\Models\Task;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $tasks = Task::where('is_decomposed', 0)
                         ->where('worker_id', null)
                         ->get();
            if($tasks->count() != 0){
                foreach($tasks as $task){
                    $worker = Worker::where('domain', $task->domain)
                                    ->where('is_available', true)
                                    ->first();
                    if($worker){
                        $task->worker_id = $worker->id;
                    }
                }
            }
        })->everyTenMinutes();
        $schedule->call(function () {
            $microtasks = Microtask::join('tasks', 'tasks.id', '=', 'microtasks.task_id')
                         ->select('microtasks.*', 'tasks.domain')
                         ->where('worker_id', null)
                         ->get();
            if($microtasks->count() != 0){
                foreach($microtasks as $microtask){
                    $worker = Worker::where('domain', $microtask->domain)
                                    ->where('is_available', true)
                                    ->first();
                    if($worker){
                        $microtask->worker_id = $worker->id;
                        $microtask->duration = 24;
                        $microtask->assignment_date = Carbon::now();
                    }
                }
            }
        })->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
