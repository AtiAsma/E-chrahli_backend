<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Microtask;
use App\Models\Worker;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    $request->validate([
        'body' => 'required',
        'type' => 'required|in:questions,explanation',
        'domain' => 'required|in:Maths,Physics,Sciences',
        'student_id' => 'required',
    ]);

    $task = new Task();
    $task->body = $request->input('body');
    $task->type = $request->input('type');
    $task->domain = $request->input('domain');
    $task->student_id = $request->input('student_id');
    $task->is_decomposed = 0;

    if($task->type == 'explanation'){
        $microtasks = self::decomposeTask($task);
        if($microtasks->count() != 0){
            $task->is_decomposed = 1;
            $microtasksToBeAssigned = collect();
            foreach($microtasks as $microtask){
                $newMicrotask = new Microtask();
                $newMicrotask->body = $microtask->microtask;
                $newMicrotask->task_id = $task->id;
                $newMicrotask->save();
                $microtasksToBeAssigned->push($newMicrotask);
            }
        }
    }else if($task->type == 'questions'){}
    // $task->save();

    return response()->json([
        'microtasks' => $microtasks[0]->microtask,
        'message' => 'Task created successfully',
        'task' => $task,
    ], 201);
}


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Decompose the task into microtasks.
     *
     * @param  Object  $task
     * @return \Illuminate\Support\Collection $microtasks
     */
    public function decomposeTask($task)
    {
        $words = [];
        $microtasks = collect();
        $words = explode(" ", $task->body);
        foreach ($words as $word) {
            $matching_keywords = Keyword::join('keywords_microtasks', 'keywords.id', '=', 'keywords_microtasks.keyword_id')
                                        ->where('keyword', $word)
                                        ->select('microtask')
                                        ->get();
            $microtasks->push(...$matching_keywords);
        }
        if($microtasks->count() == 0){
            $worker = Worker::where('domain', $task->domain)
                                ->where('is_available', 1)
                                ->first();
            if($worker){
                $task->worker_id = $worker->id;
            }
        }
        return $microtasks;
    }

    /**
     * Decompose the task into microtasks.
     *
     * @param  array  $microtasks
     * @return \Illuminate\Http\Response
     */
    public function assignMicrotasks($microtasks, $domain)
    {
        $workers = Worker::where('domain', $domain)
                                ->where('is_available', true)
                                ->orderBy('rating', 'desc')
                                ->get();

        foreach ($microtasks as $microtask){
            while($workers->size() != 0){
                $microtask->worker_id = $workers[0]->id;
                $microtask->duration = 24;
                $microtask->assignment_date = Carbon::now();
                $workers->shift();
            }
        }
    }
}
