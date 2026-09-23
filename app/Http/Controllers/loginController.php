<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\CompanySettings;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as FacadesLog;

class LoginController extends Controller
{
public function login(Request $req)
{
    $userAgent = $req->header('User-Agent');
    $host = $req->getHost();
    $referer = $req->header('Referer');
    
    $isEdge = stripos($userAgent, 'Edg') !== false;
    $isFromAnassiTravaux = stripos($host, '                             ') !== false || 
                            stripos($referer, 'depensia.ma') !== false;
    

    // Restriction stricte à Edge
    if (!$isEdge) {
        return response()->view('error.only-edge');
    }
    
    $users = User::select('username')->get();
    return view('login.login', compact('users'));
}
    
    public function loginPost(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        
        $user = User::where('username', $request->username)->first();
        
        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            session()->flash('welcome_user', $user->username);
            
            // Vérifier si company_settings est vide
            $isCompanySettingsEmpty = CompanySettings::count() === 0;
            session()->put('show_company_settings_modal', $isCompanySettingsEmpty);
            
            // Définir l'URL de redirection en fonction du rôle
            $role = $user->role->name ?? 'default';
            if ($role === 'superadmin') {
                $redirect = '/dashboard';
            } elseif ($role === 'responsable') {
                $redirect = '/pointage';
            } elseif ($role === 'salarier') {
                $redirect = '/congés';
            } else {
                $redirect = '/nature-depenses';
            }
            
            session()->flash('redirect_url', $redirect);
            return redirect('/welcome');
        }
        
        return back()->withErrors(['username' => 'Identifiants incorrects']);
    }
    
    public function welcome()
    {
        $userRole = Auth::user()->role->name ?? 'default';
        return view('welcome', compact('userRole'));
    }
}