<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FPS;

class FPSController extends Controller
{
    public function index()
    {
        $fpsRecords = FPS::all();
        return response()->json($fpsRecords);
    }

    public function details($id)
    {
        $fps = FPS::findOrFail($id);

        // Decode questions stored in `data`
        $questions = collect($fps->data)->map(function ($item, $index) {
            // Extract options as plain array of strings
            $options = collect($item['options'])->pluck('option')->toArray();

            // Find the index of the correct option
            $correctIndex = collect($item['options'])->search(function ($opt) {
                return $opt['is_correct'] === true;
            });

            return [
                'id' => $index + 1, // assign incremental id
                'question' => $item['question'],
                'options' => $options,
                'correct' => $correctIndex,
                'explanation' => $item['explanation'] ?? null,
            ];
        });

        return response()->json($questions);
    }

}
