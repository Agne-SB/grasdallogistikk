<?php

namespace App\Http\Controllers;

use App\Models\Project;

class ProjectsController extends Controller
{
    public function index()
    {
        $projects = Project::orderByDesc('updated_at')->paginate(50);
        return view('projects.index', compact('projects'));
    }
}
