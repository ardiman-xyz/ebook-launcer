<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // Load activation data
        $activationData = json_decode(Storage::get('activation.json'), true);
        
        return view('dashboard.index', compact('activationData'));
    }
    
    public function settings()
    {
        $activationData = json_decode(Storage::get('activation.json'), true);
        
        return view('dashboard.settings', compact('activationData'));
    }
    
    public function licenseInfo()
    {
        $activationData = json_decode(Storage::get('activation.json'), true);
        
        return view('dashboard.license-info', compact('activationData'));
    }
}