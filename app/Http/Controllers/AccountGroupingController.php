<?php

namespace App\Http\Controllers;

use App\Models\AccountGrouping;
use Illuminate\Http\Request;

class AccountGroupingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:akun,budget',
            'budget_type' => 'required_if:type,budget|in:debit,credit',
        ]);

        // Prevent duplicate name per type
        if (AccountGrouping::where('name', $request->name)->where('type', $request->type)->exists()) {
            return response()->json([
                'message' => 'Group name already exists for this type'
            ], 422);
        }

        $data = AccountGrouping::create([
            'name' => $request->name,
            'type' => $request->type,
            'balance_side' => $request->type === 'budget' ? $request->budget_type : null,
        ]);

        return response()->json([
            'message' => 'Created successfully',
            'data' => $data,
        ]);
    }

    /**
     * Update grouping
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:akun,budget',
            'budget_type' => 'required_if:type,budget|in:debit,credit',
        ]);

        $group = AccountGrouping::findOrFail($id);

        // Check duplicate (exclude current ID)
        if (
            AccountGrouping::where('name', $request->name)
            ->where('type', $request->type)
            ->where('id', '!=', $id)
            ->exists()
        ) {
            return response()->json([
                'message' => 'Group name already exists for this type'
            ], 422);
        }

        $group->update([
            'name' => $request->name,
            'type' => $request->type,
            'balance_side' => $request->type === 'budget' ? $request->budget_type : null,
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $group,
        ]);
    }

    /**
     * Delete grouping
     */
    public function destroy($id)
    {
        $group = AccountGrouping::findOrFail($id);
        $group->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
