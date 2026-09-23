<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // public function destroy($id)
    // {
    //     try {
    //         $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
    //         $notification->delete();
    //         return response()->json(['success' => true, 'message' => 'Notification supprimée']);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
    //     }
    // }
    public function deleteAll()
    {
        try {
            Auth::user()->notifications()->delete();
            return response()->json(['success' => true, 'message' => 'Toutes les notifications ont été supprimées']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }
    }
}
