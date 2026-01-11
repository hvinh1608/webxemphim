<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Gửi lại email xác thực
     */
    public function resendVerification(Request $request)
    {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            // Không trả về thông tin chi tiết để tránh dò email
            return response()->json(['success' => true, 'message' => 'Nếu email tồn tại, chúng tôi đã gửi lại email xác thực!']);
        }
        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email đã xác thực'], 400);
        }
        $actionUrl = url('/verify?token=' . urlencode($user->verification_token));
        Mail::to($user->email)->send(new VerifyEmail($user, $actionUrl));
        return response()->json(['success' => true, 'message' => 'Đã gửi lại email xác thực!']);
    }
    /**
     * Gửi email đặt lại mật khẩu
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'email.exists' => 'Email này không tồn tại trong hệ thống'
        ]);

        $user = User::where('email', $request->email)->first();

        // Tạo token reset password
        $resetToken = \Illuminate\Support\Str::random(64);
        $user->update([
            'password_reset_token' => $resetToken,
            'password_reset_expires_at' => now()->addHours(1) // Token hết hạn sau 1 giờ
        ]);

        // Gửi email reset password
        try {
            $resetUrl = 'https://funny-naiad-a7116a.netlify.app/reset-password?token=' . $resetToken;
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ResetPassword($user, $resetUrl));
        } catch (\Exception $e) {
            \Log::error('Failed to send reset password email: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.']);
        }

        return response()->json(['success' => true, 'message' => 'Email đặt lại mật khẩu đã được gửi!']);
    }

    /**
     * Đặt lại mật khẩu
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed'
        ], [
            'token.required' => 'Token không hợp lệ',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp'
        ]);

        $user = User::where('password_reset_token', $request->token)
                   ->where('password_reset_expires_at', '>', now())
                   ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn.']);
        }

        // Cập nhật mật khẩu và xóa token
        $user->update([
            'password' => $request->password, // setPasswordAttribute sẽ tự động hash
            'password_reset_token' => null,
            'password_reset_expires_at' => null
        ]);

        return response()->json(['success' => true, 'message' => 'Mật khẩu đã được đặt lại thành công!']);
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Tên không được để trống',
            'name.unique' => 'Tên này đã được sử dụng',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại',
            'password.required' => 'Mật khẩu không được để trống',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // setPasswordAttribute will hash
            'email_verified_at' => now(), // Auto verify for simplicity
            'verification_token' => base64_encode(Str::random(48)), // URL-safe token
        ]);

        // Gửi email xác nhận
        try {
            $actionUrl = url('/verify?token=' . urlencode($user->verification_token));
            Mail::to($user->email)->send(new VerifyEmail($user, $actionUrl));
        } catch (\Exception $e) {
            // Log error nhưng không fail registration
            \Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công! Vui lòng kiểm tra email để xác nhận tài khoản.',
            'user' => $user,
            'requires_verification' => true
        ], 201);
    }

    /**
     * Đăng nhập
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ',
            'password.required' => 'Mật khẩu không được để trống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->remember ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        $user = Auth::user();

        // Kiểm tra email đã được xác nhận chưa
        // OAuth users đã được verify từ provider, không cần verify lại
        if (!$user->email_verified_at && empty($user->oauth_provider)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng xác nhận email trước khi đăng nhập',
                'requires_verification' => true,
                'email' => $user->email
            ], 403);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    /**
     * Xử lý link xác thực email (truy cập từ email)
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return view('verify-result', ['success' => false, 'message' => 'Token xác thực không hợp lệ.']);
        }

        // Token trong database đã được base64 encode, và Laravel tự động urldecode
        $user = User::where('verification_token', $token)->first();
        if (!$user) {
            return view('verify-result', ['success' => false, 'message' => 'Token xác thực không hợp lệ hoặc đã hết hạn.']);
        }

        if ($user->email_verified_at) {
            return redirect('https://funny-naiad-a7116a.netlify.app/login?verification=already_verified');
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->save();

        // Redirect trực tiếp đến Vue app với thông báo thành công
        return redirect('https://funny-naiad-a7116a.netlify.app/login?verification=success');
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Redirect to Facebook OAuth
     */
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        \Log::info('Google OAuth callback received', ['url' => request()->fullUrl()]);

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            \Log::info('Google OAuth User:', [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'id' => $googleUser->getId()
            ]);

            // Tìm user theo email từ Google
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User đã tồn tại - kiểm tra loại user
                if (!empty($user->oauth_provider)) {
                    // User đã đăng ký bằng OAuth - kiểm tra provider
                    if ($user->oauth_provider === 'google') {
                        // Cùng provider - cho phép login, cập nhật avatar nếu cần
                        if (!$user->avatar && $googleUser->getAvatar()) {
                            $user->update(['avatar' => $googleUser->getAvatar()]);
                            \Log::info('Updated avatar for existing OAuth user:', ['user_id' => $user->id]);
                        }

                        \Log::info('Existing Google OAuth user login:', ['user_id' => $user->id, 'email' => $user->email]);
                        $token = $user->createToken('auth_token')->plainTextToken;
                        \Log::info('Token created for existing OAuth user:', ['token' => $token]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?google_success=1&token=' . urlencode($token));
                    } else {
                        // Provider khác đã dùng email này - BLOCK
                        \Log::warning('Google login blocked - email used by different provider:', [
                            'email' => $user->email,
                            'existing_provider' => $user->oauth_provider,
                            'attempted_provider' => 'google'
                        ]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?google_email_exists=1&email=' . urlencode($user->email));
                    }
                } else {
                    // User chỉ đăng ký bằng email/password - kiểm tra xem có thể link OAuth không
                    if (!empty($user->password)) {
                        // Có password - không cho phép link OAuth, phải login bằng password
                        \Log::warning('Google login attempt for email/password user:', ['email' => $user->email]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?google_email_exists=1&email=' . urlencode($user->email));
                    } else {
                        // Không có password và không có oauth_provider - có thể link OAuth
                        $user->update([
                            'oauth_provider' => 'google',
                            'oauth_id' => $googleUser->getId(),
                            'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                        ]);
                        \Log::info('Linked Google OAuth to existing user:', ['user_id' => $user->id]);
                        $token = $user->createToken('auth_token')->plainTextToken;
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?google_success=1&token=' . urlencode($token));
                    }
                }
            } else {
                // Tạo user mới từ thông tin Google
                \Log::info('Creating new user from Google:', [
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar()
                ]);

                // Tạo unique name nếu bị trùng
                $baseName = $googleUser->getName();
                $name = $baseName;
                $counter = 1;

                while (User::where('name', $name)->exists()) {
                    $name = $baseName . '_' . $counter;
                    $counter++;
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(), // Lưu avatar từ Google
                    'password' => null, // Không có password cho OAuth users
                    'email_verified_at' => now(), // Google email đã verify
                    'oauth_provider' => 'google',
                    'oauth_id' => $googleUser->getId(),
                ]);

                \Log::info('New user created:', ['user_id' => $user->id, 'final_name' => $name]);
                $token = $user->createToken('auth_token')->plainTextToken;
                \Log::info('Token created for new user:', ['token' => $token]);

                return redirect('https://funny-naiad-a7116a.netlify.app/login?google_success=1&token=' . urlencode($token) . '&new_user=1');
            }
        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            \Log::error('Google OAuth error details:', ['exception' => $e]);
            return redirect('https://funny-naiad-a7116a.netlify.app/login?google_error=1');
        }
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback()
    {
        \Log::info('Facebook OAuth callback STARTED', ['url' => request()->fullUrl(), 'all_params' => request()->all()]);

        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            \Log::info('Facebook OAuth callback received', ['url' => request()->fullUrl()]);
            \Log::info('Facebook user data:', [
                'name' => $facebookUser->getName(),
                'email' => $facebookUser->getEmail(),
                'id' => $facebookUser->getId(),
                'avatar' => $facebookUser->getAvatar()
            ]);

            // Tìm user theo email từ Facebook
            $user = User::where('email', $facebookUser->getEmail())->first();

            if ($user) {
                // User đã tồn tại - kiểm tra loại user
                if (!empty($user->oauth_provider)) {
                    // User đã đăng ký bằng OAuth - kiểm tra provider
                    if ($user->oauth_provider === 'facebook') {
                        // Cùng provider - cho phép login, cập nhật avatar nếu cần
                        if (!$user->avatar && $facebookUser->getAvatar()) {
                            $user->update(['avatar' => $facebookUser->getAvatar()]);
                            \Log::info('Updated avatar for existing OAuth user:', ['user_id' => $user->id]);
                        }

                        \Log::info('Existing Facebook OAuth user login:', ['user_id' => $user->id, 'email' => $user->email]);
                        $token = $user->createToken('auth_token')->plainTextToken;
                        \Log::info('Token created for existing OAuth user:', ['token' => $token]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_success=1&token=' . urlencode($token));
                    } else {
                        // Provider khác đã dùng email này - BLOCK
                        \Log::warning('Facebook login blocked - email used by different provider:', [
                            'email' => $user->email,
                            'existing_provider' => $user->oauth_provider,
                            'attempted_provider' => 'facebook'
                        ]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_email_exists=1&email=' . urlencode($user->email));
                    }
                } else {
                    // User chỉ đăng ký bằng email/password - kiểm tra xem có thể link OAuth không
                    if (!empty($user->password)) {
                        // Có password - không cho phép link OAuth, phải login bằng password
                        \Log::warning('Facebook login attempt for email/password user:', ['email' => $user->email]);
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_email_exists=1&email=' . urlencode($user->email));
                    } else {
                        // Không có password và không có oauth_provider - có thể link OAuth
                        $user->update([
                            'oauth_provider' => 'facebook',
                            'oauth_id' => $facebookUser->getId(),
                            'avatar' => $facebookUser->getAvatar() ?: $user->avatar,
                        ]);
                        \Log::info('Linked Facebook OAuth to existing user:', ['user_id' => $user->id]);
                        $token = $user->createToken('auth_token')->plainTextToken;
                        return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_success=1&token=' . urlencode($token));
                    }
                }
            } else {
                // Tạo user mới từ thông tin Facebook
                \Log::info('Creating new user from Facebook:', [
                    'email' => $facebookUser->getEmail(),
                    'avatar' => $facebookUser->getAvatar()
                ]);

                // Tạo unique name nếu bị trùng
                $baseName = $facebookUser->getName();
                $name = $baseName;
                $counter = 1;

                while (User::where('name', $name)->exists()) {
                    $name = $baseName . '_' . $counter;
                    $counter++;
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $facebookUser->getEmail(),
                    'password' => null, // Không có password cho OAuth users
                    'email_verified_at' => now(), // Facebook email đã verify
                    'avatar' => $facebookUser->getAvatar(),
                    'oauth_provider' => 'facebook',
                    'oauth_id' => $facebookUser->getId(),
                ]);

                \Log::info('New Facebook user created:', ['user_id' => $user->id, 'final_name' => $name]);
                $token = $user->createToken('auth_token')->plainTextToken;
                \Log::info('Token created for new Facebook user:', ['token' => $token]);

                return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_success=1&new_user=1&token=' . urlencode($token));
            }
        } catch (\Exception $e) {
            \Log::error('Facebook OAuth error: ' . $e->getMessage());
            \Log::error('Facebook OAuth error details:', ['exception' => $e]);
            return redirect('https://funny-naiad-a7116a.netlify.app/login?facebook_error=1');
        }
    }
}
