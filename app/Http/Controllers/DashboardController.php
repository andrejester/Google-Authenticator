<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $barang = Barang::count();
        $users = User::count();

        return view('dashboard.dashboard', [
            'barang' => $barang,
            'users'  => $users,
        ]);
    }
}
