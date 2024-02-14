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
            $workers = Worker::where('is_available', 1)->get();
            $usedWorkers = collect();
            foreach($tasks as $task){
                foreach($workers as $worker){
                    if($task->domain == $worker->domain){
                        if(!$usedWorkers->contains($worker->id)){
                            $task->worker_id = $worker->id;
                            $task->save();
                            $usedWorkers->push($worker->id);
                            break;
                        }
                    }
                }
            }
        })->everyMinute();
        $schedule->call(function () {
            $microtasks = Microtask::join('tasks', 'tasks.id', '=', 'microtasks.task_id')
                            ->select('microtasks.*', 'tasks.domain')
                            ->where('microtasks.worker_id', null)
                            ->get();
            $workers = Worker::where('is_available', 1)->get();
            $usedWorkers = collect();
            foreach($microtasks as $microtask){
                $task = Task::find($microtask->task_id);
                foreach($workers as $worker){
                    if($task->domain == $worker->domain){
                        if(!$usedWorkers->contains($worker->id)){
                            $microtask->worker_id = $worker->id;
                            $microtask->assignment_date = Carbon::now();
                            $microtask->save();
                            $usedWorkers->push($worker->id);
                            break;
                        }
                    }
                }
            }
        })->everyMinute();
        $schedule->call(function () {
            $microtasks = Microtask::where('response', null)->where('assignment_date', '<', now()->subHours(12))->get();
            $workers = Worker::where('is_available', 1)->get();
            $usedWorkers = collect();
            foreach($microtasks as $microtask){
                $task = Task::find($microtask->task_id);
                foreach($workers as $worker){
                    if($task->domain == $worker->domain){
                        if($microtask->worker_id != $worker->id){
                            if(!$usedWorkers->contains($worker->id)){
                                $microtask = Microtask::find($microtask->id);
                                $microtask->worker_id = $worker->id;
                                $microtask->assignment_date = Carbon::now();
                                $microtask->save();
                                $usedWorkers->push($worker->id);
                                break;
                            }
                        }
                    }
                }
            }
        })->everyMinute();
    }

    /** 
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(app_path('Console/Commands'));
        require base_path('routes/console.php');
    }
}