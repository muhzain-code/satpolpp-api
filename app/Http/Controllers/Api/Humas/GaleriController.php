<?php

namespace App\Http\Controllers\Api\Humas;

use App\Http\Controllers\Controller;
use App\Services\Humas\GaleriService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GaleriController extends Controller
{

    use ApiResponse;

    protected GaleriService $service;

    public function __construct(GaleriService $service) {
        $this->service = $service;
    }
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->index($perPage, $currentPage);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
