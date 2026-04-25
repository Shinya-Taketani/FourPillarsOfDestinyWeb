<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AnalysisWebController extends Controller
{
    /**
     * 鑑定入力画面を表示
     */
    public function index(): Response
    {
        return Inertia::render('Analysis/Index');
    }
}
