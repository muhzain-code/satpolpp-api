<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Services\User\UserService;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getAllKomandan(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $currentPage = $request->input('page', 1);

        $result = $this->userService->getAllKomandan($request, $perPage, $currentPage);

        return response()->json($result);
    }
}
