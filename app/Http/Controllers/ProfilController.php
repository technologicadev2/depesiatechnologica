<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function index()
    {
        return view('profil.index');
    }
    public function updateProfile(Request $request)
    {
        $request->validate([
            'username' => 'required|min:3|max:255|unique:users,username,' . Auth::id(),
            'profil_file' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::user();
        $data = $request->only(['username']);
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('profil_file')) {
            if ($user->profil_image && $user->profil_image !== 'default_profil.png') {
                Storage::disk('public')->delete('uploads/' . $user->profil_image);
            }
            $imageName = 'profile-' . time() . '-' . Auth::id() . '.' . $request->profil_file->extension();
            $request->file('profil_file')->move(public_path('storage/uploads'), $imageName);
            $data['profil_image'] = $imageName;
        }

        $user->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'new_path' => $data['profil_image'] ?? $user->profil_image
            ]);
        }

        return redirect()->back()->with('success', 'Profil modifié avec succès');
    }
    public function deleteProfileImage()
    {
        $user = Auth::user();

        if ($user->profil_image && $user->profil_image !== 'default_profil.png') {
            if (file_exists(public_path('storage/uploads/' . $user->profil_image))) {
                unlink(public_path('storage/uploads/' . $user->profil_image));
            }

            $user->update([
                'profil_image' => 'default_profil.png',
                'updated_by' => Auth::id(),
            ]);
        }

        return redirect()->back()->with('success', 'Image de profil supprimée avec succès');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'required' => 'Ce champ est obligatoire',
            'min' => 'Le mot de passe doit comporter au moins :min caractères',
            'confirmed' => 'La confirmation du mot de passe ne correspond pas',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Mot de passe actuel incorrect');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Mot de passe modifié avec succès');
    }
}
