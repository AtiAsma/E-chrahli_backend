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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    $request->validate([
        'body' => 'string',
        'type' => 'required|in:Exercise,Explanation',
        'domain' => 'required|in:Maths,Physics,Sciences',
        'student_id' => 'required|integer',
        'questions' => 'array',
    ]);

    $task = new Task();
    if($request->input('body')){
        $task->body = $request->input('body');
    }
    $task->type = $request->input('type');
    $task->domain = $request->input('domain');
    $task->student_id = $request->input('student_id');
    $task->is_decomposed = false;
    $task->save();

    if($task->type == 'Explanation'){
        $microtasks = self::decomposeTask($task);
        $microtasksToBeAssigned = collect();
        if($microtasks->count() != 0){
            $task->is_decomposed = true;
            $task->save();
            foreach($microtasks as $microtask){
                $newMicrotask = new Microtask();
                $newMicrotask->body = $microtask->microtask;
                $newMicrotask->task_id = $task->id;
                $newMicrotask->save();
                $microtasksToBeAssigned->push($newMicrotask);
            }
        }
        $microtasksReturned = self::assignMicrotasks($microtasksToBeAssigned, $task->domain);
    }else if($task->type == 'Exercise'){
        $questions = $request->input('questions');
        $task->is_decomposed = true;
        $task->save();
        $microtasksToBeAssigned = collect();
        foreach($questions as $question){
            $newMicrotask = new Microtask();
            $newMicrotask->body = $question;
            $newMicrotask->task_id = $task->id;
            $newMicrotask->save();
            $microtasksToBeAssigned->push($newMicrotask);
        }
        $microtasksReturned = self::assignMicrotasks($microtasksToBeAssigned, $task->domain);
    }

    return response()->json(['id' => $task->id,'message' => 'Task created successfully',
    ]);
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
                $task->save();
            }
        }
        return $microtasks;
    }

    /**
     *
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
            while($workers->count() != 0){
                $microtask->worker_id = $workers[0]->id;
                $microtask->assignment_date = Carbon::now();
                $microtask->save();
                $workers->shift();
                break;
            }
        }
        return $microtasks;
    }



    /**
     * get the history of what the student asked.
     *
     * @param  integer  $id
     * @return \Illuminate\Http\Response
     */
    public function viewHistory($student_id)
    {
        $tasks = Task::where('student_id', $student_id)->get();
        return $tasks;
    }

    /**
     * Compose the answer of the task.
     *
     * @param  integer  $id
     * @return \Illuminate\Http\Response
     */
    public function composeAnswer($id)
    {
        $microtasks = Microtask::where('task_id', $id)
                                ->get();
        return $microtasks;
    }
    

    public function getLastAnswer($student_id)
    {
        $lastTask = Task::where('student_id', $student_id)
                        ->orderBy('created_at', 'desc')
                        ->first();

        $microtasks = Microtask::where('task_id', $lastTask->id)->get();
        return $microtasks;
    }

    /**
     * Get response of microtask from worker.
     *
     * @param  integer  $id
     * @return \Illuminate\Http\Response
     */
    public function answerMicrotask(Request $request ,$id)
    {
        $request->validate([
            'answer' => 'required|string',
        ]);
        $microtask = Microtask::find($id);
        if($microtask){
            $microtask->response = $request->input('answer');
            $microtask->save();
            return $microtask;
        }
        else {
            return response()->json(['message' => 'Microtask not found'])->setStatusCode(404);
        }
    }

    /**
     * Get microtasks of microtask from worker.
     *
     * @param  integer  $id
     * @return \Illuminate\Http\Response
     */
    public function getMicrotasksFromWorker(Request $request ,$id)
    {
        $request->validate([
            'microtasks' => 'required|array',
        ]);
        $task = Task::find($id);
        if(!$task->is_decomposed){
            $microtasks = $request->input('microtasks');
            $microtasksToBeAssigned = collect();
            foreach($microtasks as $microtask){
                $newMicrotask = new Microtask();
                $newMicrotask->body = $microtask;
                $newMicrotask->task_id = $id;
                $newMicrotask->save();
                $microtasksToBeAssigned->push($newMicrotask);
            }

            $task->is_decomposed = true;
            $task->save();

            $microtasksReturned = self::assignMicrotasks($microtasksToBeAssigned, $task->domain);

            return response()->json(['message' => 'Your Microtasks are stored successfully'])->setStatusCode(200);return response()->json(['message' => 'Your Microtasks are stored successfully'])->setStatusCode(200);
        }
    }


    /**
     * The worker gets the microtasks assigned to him.
     *
     * @param  array  $microtasks
     * @return \Illuminate\Http\Response
     */
    public function getMyMicrotasks($id)
    {
        $microtasks = Microtask::where('worker_id', $id)
                                ->where('response', null)
                                ->select('id', 'body', \DB::raw("TIMESTAMPDIFF(HOUR, NOW(), DATE_ADD(assignment_date, INTERVAL 14 HOUR)) AS deadline"))
                                ->get();

        $returned_microtasks = collect();

        foreach($microtasks as $microtask){
            if($microtask->deadline > 0){
                $microtask->deadline = $microtask->deadline . " hours left";
                $returned_microtasks->push($microtask);
            }
        }

        return $returned_microtasks;
    }
    


    /**
     * The worker gets the tasks assigned to him.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getMyTasks($id)
    {
        $tasks = Task::where('is_decomposed', 0)
                     ->where('worker_id', $id)
                     ->get();

        return $tasks;
    }
}
