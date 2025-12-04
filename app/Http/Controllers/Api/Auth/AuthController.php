<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;
    protected AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $currentPage = $request->input('page', 1);
        $result = $this->service->getAll($perPage, $currentPage, $request);
        return $this->successResponseWithMeta($result['data'], $result['message'], $result['meta']);
    }

    public function show($id): JsonResponse
    {
        $result = $this->service->findById($id);
        return $this->successResponse($result['data'], $result['message']);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->service->register($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login($request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function update($id, UpdateUserRequest $request): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());
        return $this->successResponse($result['data'], $result['message']);
    }

    public function logout(Request $request): JsonResponse
    {
        $result = $this->service->logout($request->user());
        return $this->successResponse(null, $result['message']);
    }

    public function forgotPassword(ForgotPasswordRequest $req): JsonResponse
    {
        $status = $this->service->sendResetLink($req->email);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link terkirim.'])
            : response()->json(['message' => __($status)], 500);
    }

    public function resetPassword(ResetPasswordRequest $req): JsonResponse
    {
        $status = $this->service->resetPassword($req->validated());

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password berhasil direset.'])
            : response()->json(['message' => __($status)], 500);
    }

    public function changePassword(ChangePasswordRequest $req): JsonResponse
    {
        $this->service->changePassword(
            $req->user(),
            $req->current_password,
            $req->new_password
        );

        return response()->json(['message' => 'Password telah diubah. Silakan login ulang.']);
    }
}
