<?php

namespace App\Http\Controllers;

use App\Models\QontakPipeline;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function getpipelines()
    {
        $data = QontakPipeline::all();
        return $this->successResponse($data);
    }
}
