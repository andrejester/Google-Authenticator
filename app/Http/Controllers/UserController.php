<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::all(); // Mengambil semua data pengguna dari database
        return view('users.index', compact('users')); // Mengirim data ke view
    }

    public function view($id)
    {
        $user = User::findOrFail($id);

        // Inisialisasi Google2FA
        $google2fa = new Google2FA();

        // Dapatkan secret 2FA pengguna
        $secret = $user->google2fa_secret;

        // Buat QR Code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),  // Nama aplikasi
            $user->email,        // Email pengguna
            $secret              // Secret key pengguna
        );

        // Generate QR code sebagai gambar base64
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCodeImage = base64_encode($writer->writeString($qrCodeUrl));

        // Kirim data ke view
        return view('users.view', compact('user', 'qrCodeImage'));
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
