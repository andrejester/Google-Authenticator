<?php

namespace App\Http\Controllers;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::all(); // Mengambil semua data pengguna dari database
        return view('users.index', compact('users')); // Mengirim data ke view
    }

    public function create()
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

        return view('users.users-add', [
            'title' => 'Register',
            'qrCode' => $qrCode,
            'secret' => $secret
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
            'passwordConfirm' => 'required|same:password',
            'google2fa_secret' => 'required'
        ]);

        $validated['password']         = Hash::make($request['password']);

        $user = User::create($validated);

        Alert::success('Success', 'User has been saved !');
        return redirect('/users');
    }

    public function view($id)
    {
        $users = User::findOrFail($id);

        // Inisialisasi Google2FA
        $google2fa = new Google2FA();

        // Dapatkan secret 2FA pengguna
        $secret = $users->google2fa_secret;

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
        $googleChartApiUrl = $writer->writeString($QR_Image);

        return view('users.view', compact('users', 'googleChartApiUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id_users)
    {
        $users = User::findOrFail($id_users);

        return view('users.users-edit', [
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id_users)
    {
        $user = User::findOrFail($id_users);

        // Validasi data yang diterima
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        // Update data pengguna
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        Alert::info('Success', 'Barang has been updated !');
        return redirect('/users');
    }
}
