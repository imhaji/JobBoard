<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JobFilterService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');
        $jobs = JobFilterService::apply($filter);
        return response()->json($jobs);
    }
}
