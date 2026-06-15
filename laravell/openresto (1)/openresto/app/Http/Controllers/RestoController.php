<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RestoController extends Controller
{
    // 1. Halaman Utama Pelanggan
    public function index()
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan login admin terlebih dahulu.');
        }

        return redirect()->route('menu');
    }

    // 2. Simpan Data Pelanggan (Deprecated - logic moved to order)
    public function storeSession(Request $request)
    {
        return redirect()->route('index');
    }

    // 3. Tampilkan Halaman Daftar Menu
    public function menu()
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan login admin terlebih dahulu.');
        }

        $menus = DB::table('menus')->get();

        return view('daftar_menu', compact('menus'));
    }

    // 4. Proses Simpan Orderan
    public function order(Request $request)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login')->with('error', 'Silakan login admin terlebih dahulu.');
        }

        $request->validate([
            'items' => 'required|array',
            'customer_name' => 'required|string',
            'customer_hp' => 'nullable|string',
            'order_type' => 'required|in:dine,takeaway',
            'table_number' => 'required_if:order_type,dine',
        ]);

        $table = $request->order_type === 'dine' ? (int)$request->table_number : 0;

        $items = $request->input('items', []);
        $notes = $request->input('notes', []);
        $total_price = 0;
        $valid_items = [];

        foreach ($items as $menu_id => $qty) {
            $qty = (int)$qty;

            if ($qty > 0) {
                $menu = DB::table('menus')->where('id', $menu_id)->first();

                if ($menu) {
                    $total_price += ($menu->price * $qty);

                    $valid_items[] = [
                        'menu_id' => $menu_id,
                        'quantity' => $qty,
                        'price' => $menu->price,
                        'notes' => $notes[$menu_id] ?? '',
                    ];
                }
            }
        }

        if ($total_price > 0) {
            // Menggunakan transaksi database untuk memastikan integritas data
            $order_id = DB::transaction(function () use ($table, $request, $total_price, $valid_items) {
                $id = DB::table('orders')->insertGetId([
                    'table_number' => $table,
                    'customer_name' => $request->customer_name,
                    'customer_hp' => $request->customer_hp,
                    'order_type' => $request->order_type,
                    'admin_user' => session('admin_username') ?? null,
                    'total_price' => $total_price,
                    'payment_method' => 'Cash',
                    'amount_paid' => $total_price,
                    'amount_change' => 0,
                    'created_at' => now(), // Menetapkan tanggal dan jam pemesanan saat ini
                    'updated_at' => now()
                ]);

                foreach ($valid_items as $item) {
                    DB::table('order_details')->insert([
                        'order_id' => $id,
                        'menu_id' => $item['menu_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'],
                        'price' => $item['price'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                return $id;
            });

            return redirect()->route('order.receipt', ['id' => $order_id, 'print' => 1])->with('success', 'Pesanan berhasil diproses!');
        }

        return redirect()->back()->with('error', 'Silakan pilih minimal 1 menu sebelum checkout!');
    }

    // 4a. Halaman Struk
    public function receipt(Request $request, $id)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) return redirect()->route('menu');

        $details = DB::table('order_details')
            ->join('menus', 'order_details.menu_id', '=', 'menus.id')
            ->select('order_details.*', 'menus.name')
            ->where('order_id', $id)
            ->get();

        $print = $request->has('print');

        return view('struk', compact('order', 'details', 'print'));
    }

    // 5. Form Login Admin
    public function loginForm()
    {
        if (session()->has('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin_login');
    }

    // 6. Proses Login Admin
    public function loginProcess(Request $request)
    {
        $username_admin = "admin";
        $password_admin = "admin123";

        if (
            $request->username == $username_admin &&
            $request->password == $password_admin
        ) {
            session([
                'admin_logged_in' => true,
                'admin_username' => $request->username
            ]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->back()->with('error', 'Username atau Password salah!');
    }

    // 7. Logout
    public function logout()
    {
        session()->forget([
            'admin_logged_in',
            'admin_username'
        ]);

        return redirect()->route('admin.login')->with('success', 'Berhasil keluar!');
    }

    // 8. Dashboard Admin
    public function dashboard()
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $tahun_ini = date('Y');

        $sales_data = DB::table('orders')
            ->select(
                DB::raw('MONTH(created_at) as bulan'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereYear('created_at', $tahun_ini)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get();

        $chart_data = array_fill(1, 12, 0);
        $total_omzet = 0;

        foreach ($sales_data as $data) {
            if ($data->bulan >= 1 && $data->bulan <= 12) {
                $chart_data[$data->bulan] = (int)$data->total;
                $total_omzet += $data->total;
            }
        }

        $orders = DB::table('orders')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($orders as $order) {
            $order->details = DB::table('order_details')
                ->join('menus', 'order_details.menu_id', '=', 'menus.id')
                ->select('order_details.*', 'menus.name')
                ->where('order_details.order_id', $order->id)
                ->get();
        }

        $nama_bulan = [
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember"
        ];

        return view('admin_dashboard', [
            'total_omzet' => $total_omzet,
            'chart_data' => array_values($chart_data),
            'nama_bulan' => $nama_bulan,
            'orders' => $orders,
            'tahun' => $tahun_ini
        ]);
    }

    // Admin: list menus
    public function adminMenus()
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $menus = DB::table('menus')->orderBy('id')->get();

        return view('admin_menus', compact('menus'));
    }

    // Admin: form tambah menu baru
    public function adminMenuCreate()
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        return view('admin_menu_create');
    }

    // Admin: simpan menu baru
    public function adminMenuStore(Request $request)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $insertData = [
            'name' => $request->name,
            'price' => (int)$request->price,
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Handle Gambar Hasil Crop (Base64)
        if ($request->cropped_image) {
            $img = $request->cropped_image;
            $img = preg_replace('/^data:image\/\w+;base64,/', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            $fileName = 'menu-images/' . uniqid() . '.png';
            Storage::disk('public')->put($fileName, $data);
            $insertData['image'] = $fileName;
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu-images', 'public');
            $insertData['image'] = $path;
        }

        DB::table('menus')->insert($insertData);

        return redirect()->route('admin.menus')->with('success', 'Menu berhasil ditambahkan');
    }

    // Admin: edit menu
    public function adminMenuEdit($id)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $menu = DB::table('menus')->where('id', $id)->first();

        if (!$menu) {
            return redirect()->route('admin.menus')->with('error', 'Menu tidak ditemukan');
        }

        return view('admin_menu_edit', compact('menu'));
    }

    // Admin: update menu
    public function adminMenuUpdate(Request $request, $id)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $updateData = [
            'name' => $request->name,
            'price' => (int)$request->price,
            'updated_at' => now()
        ];

        if ($request->cropped_image || $request->hasFile('image')) {
            // Hapus gambar lama dari storage jika ada
            $menu = DB::table('menus')->where('id', $id)->first();
            if ($menu && $menu->image) {
                Storage::disk('public')->delete($menu->image);
            }

            if ($request->cropped_image) {
                $img = $request->cropped_image;
                $img = preg_replace('/^data:image\/\w+;base64,/', '', $img);
                $img = str_replace(' ', '+', $img);
                $data = base64_decode($img);
                $fileName = 'menu-images/' . uniqid() . '.png';
                Storage::disk('public')->put($fileName, $data);
                $updateData['image'] = $fileName;
            } else {
                $path = $request->file('image')->store('menu-images', 'public');
                $updateData['image'] = $path;
            }
        }

        DB::table('menus')->where('id', $id)->update($updateData);

        return redirect()->route('admin.menus')->with('success', 'Menu diperbarui');
    }

    // Admin: delete menu
    public function adminMenuDelete($id)
    {
        if (!session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $menu = DB::table('menus')->where('id', $id)->first();
        if ($menu && $menu->image) {
            // Hapus file gambar fisik dari storage
            Storage::disk('public')->delete($menu->image);
        }

        DB::table('menus')->where('id', $id)->delete();

        return redirect()->route('admin.menus')->with('success', 'Menu dihapus');
    }
}
