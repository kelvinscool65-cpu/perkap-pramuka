<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Rules\IdentitasBelumDipakai;
use App\Rules\NoHpSeluler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    /**
     * Yang bisa diubah: nama, username, email, nomor WhatsApp, foto. ID Anggota TIDAK ada di sini
     * dan tidak pernah dibaca dari input — hanya field yang tercantum di validate() yang disimpan.
     * Email/HP baru tetap harus unik (dalam bentuk baku) terhadap SEMUA akun, termasuk yang dihapus.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge([
            'nama' => preg_replace('/\s+/u', ' ', trim((string) $request->input('nama'))),
            'email' => strtolower(trim((string) $request->input('email'))),
            'username' => strtolower(trim((string) $request->input('username'))),
            'no_hp' => trim((string) $request->input('no_hp')),
        ]);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\\pL\\pM][\\pL\\pM\\s.'\\-]*$/u"],
            'email' => ['required', 'string', 'email:rfc', 'max:150', new IdentitasBelumDipakai('email', $user->id)],
            'username' => ['required', 'string', 'min:4', 'max:30', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user->id)],
            'no_hp' => ['required', 'string', 'max:20', new NoHpSeluler, new IdentitasBelumDipakai('no_hp', $user->id)],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
            'username.unique' => 'Username sudah dipakai. Pilih yang lain.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip (-), dan garis bawah (_).',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatar', 'public');
        }

        $user->update($validated);

        return back()->with('sukses', 'Profil berhasil diperbarui.');
    }

    /** Ganti password terpisah dari update profil, demi keamanan (password lama wajib dicek) */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('sukses', 'Password berhasil diubah.');
    }
}
