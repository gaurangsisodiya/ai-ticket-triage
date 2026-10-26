<?php

namespace App\Http\Controllers;

use App\Services\Evaluation;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    /**
     * Every labeled ticket where the AI's category or team differs from the expected answer.
     */
    public function __invoke(Evaluation $evaluation): View
    {
        return view('evaluation', ['evaluation' => $evaluation->run()]);
    }
}
