<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\XP;

class XPController extends Controller
{
    public function FPSxp(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'fps_id' => 'required|exists:f_p_s,id',
            'xp_gained' => 'required|integer|min:0',
        ]);

        // Check if record already exists for this user and fps_id
        $existingRecord = FPSWin::where('customer_id', $user->id)
            ->where('fps_id', $request->fps_id)
            ->first();

        if (!$existingRecord) {
            // Create new FPSWin record
            FPSWin::create([
                'customer_id' => $user->id,
                'fps_id' => $request->fps_id,
                'score' => $request->xp_gained,
            ]);

            // Increase customer's total XP
            $user->xp += $request->xp_gained/10;
            $user->save();

            return response()->json([
                'message' => 'XP recorded successfully',
                'total_xp' => $user->xp,
            ], 201);
        }

        return response()->json([
            'message' => 'Record already exists for this FPS',
            'total_xp' => $user->xp,
        ], 200);
    }

}
