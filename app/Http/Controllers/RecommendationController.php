<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function seeRecommendations(Request $request)
    {
        $title = '';
        if($request->has('title')){
            $title = $request->title;
        }
        $domain = '';
        if($request->has('domain')){
            $domain = $request->domain;
        }
        $type = '';
        if($request->has('type')){
            $type = $request->type;
        }

        $recommendations = Recommendation::where('title', 'LIKE', '%' . $title . '%')
                                         ->where('domain', $domain)
                                         ->where('type', $type)
                                         ->get();

        return $recommendations;
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function recommendExercicesAndTopics(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:Exercise,Topic',
            'worker_id' => 'required|integer',
        ]);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|mimes:jpeg,png,jpg,pdf',
            ]);

        }

        $recommendation = new Recommendation();

        $recommendation->title = $request->input('title');
        $recommendation->type = $request->input('type');
        $recommendation->worker_id = $request->input('worker_id');
        $recommendation->file_path = $request->file->hashName();

        $recommendation->save();
        $request->file('file')->move('files', $request->file->hashName());

        return response()->json(['message' => 'Your recommendation is stored successfully'])->setStatusCode(200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getWorkerRecommendations($id)
    {
        $recommendations = Recommendation::where('worker_id', $id)
                                         ->get();

        return $recommendations;
    }

    
}
