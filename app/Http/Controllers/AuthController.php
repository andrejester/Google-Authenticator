<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FA\Google2FA as Google2FAGenerator;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;


class AuthController extends Controller
{
    public function index()
    {

        return view('auth.login', [
            'title' => 'Login',
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'google2fa_token' => 'required',
        ]);

        // Cek kredensial pengguna
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            $google2fa = new Google2FA();

            // Verifikasi token 2FA
            $secret = $request->input('google2fa_token');
            $isValid = $google2fa->verifyKey($user->google2fa_secret, $secret);

            if ($isValid) {
                // Jika 2FA valid
                Alert::success('Success', 'OK !');
                return redirect()->intended('/dashboard')->with('success', 'Login success!');
            } else {
                // Jika 2FA tidak valid
                Alert::error('Error', 'Check Again Your Data !');
                Auth::logout();
                return redirect()->back()->withErrors(['google2fa' => 'Invalid 2FA token.']);
            }
        }

        // Jika kredensial tidak valid
        return redirect()->back()->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function register()
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Generate QR code URL
        $QR_Image = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            config('app.name'),
            old('email', 'sofanipin@gmail.com'),
            $secret,
            config('app.name')
        );

        // Menggunakan BaconQrCode untuk membuat QR code
        $renderer = new ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($QR_Image);

        return view('auth.register', [
            'title' => 'Register',
            'qrCode' => $qrCode,
            'secret' => $secret
        ]);
    }

    public function process(Request $request)
    {
        $google2fa = new Google2FA();

        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
            'passwordConfirm' => 'required|same:password',
            'google2fa_secret' => 'required'
        ]);

        $validated['password']         = Hash::make($request['password']);

        $user = User::create($validated);

        Alert::success('Success', 'Register user has been successfully !');
        return redirect('/login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
        Alert::success('Success', 'Log out success !');
        return redirect('/login');
    }
}
