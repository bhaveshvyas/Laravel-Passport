<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class AuthController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input             = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user              = User::create($input);
        $success['token']  = $user->createToken('MyApp')->accessToken;
        $success['name']   = $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user             = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['name']  = $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }

    /**
     * forget password
     *
     * @param Request $request
     * @return JsonResponse
     * @author BV
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:sp_users',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        PasswordResetToken::where('email', $request->email)->delete();

        $token = mt_rand(100000, 999999);

        $passwordReset = PasswordResetToken::create([
            'email' => $request->email,
            'token' => $token,
        ]);

        // Send email to user
        Mail::to($request->email)
            ->send(new SendCodeResetPassword($token));

        return $this->sendResponse([], 'Reset code sent to your email.');
    }

    /**
     * reset password with token
     *
     * @param Request $request
     * @return JsonResponse
     * @author BV
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => 'required|string|exists:password_reset_tokens',
            'password' => 'required|string|min:6',
        ]);

        // find the code
        $passwordReset = PasswordResetToken::where('token', $request->token)->first();

        if ($passwordReset) {

            if ($passwordReset->created_at > now()->addHour()) {
                $passwordReset->delete();
                return $this->sendError('Unauthorised.', "Code is expired.");
            }

            $user           = User::firstWhere('email', $passwordReset->email);
            $user->password = bcrypt($request->password);
            $user->save();

            $passwordReset->delete();
        }

        return $this->sendResponse([], 'Password reset successfully.');
    }
}
