<?php

namespace App\Http\Controllers;
use App\Models\Unit; // <-- Import the Unit model
use Illuminate\Http\Request;
use App\Models\User;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     *
     * @return \Illuminate\Http\JsonResponse
     */

     public function assignUnit(Request $request)
     {
         $validated = $request->validate([
             'user_id' => 'required|exists:users,id',
             'unit_id' => 'required|exists:units,id',
         ]);
     
         // Unassign the tenant from any current unit before assigning a new one
         User::where('id', $validated['user_id'])->update(['unit_id' => null]);
     
         // Assign the tenant to the new unit
         $user = User::find($validated['user_id']);
         $user->unit_id = $validated['unit_id'];
         $user->save();
     
         return response()->json([
             'message' => 'Unit assigned to tenant successfully!',
             'user' => $user,
         ]);
     }
     
public function updateStatus(Request $request, $id)
{
    $unit = Unit::findOrFail($id);
    $validated = $request->validate([
        'status' => 'required|in:available,unavailable',
    ]);

    $unit->status = $validated['status'];
    $unit->save();

    return response()->json(['message' => 'Unit status updated successfully.', 'unit' => $unit]);
}

public function index()
{
    $units = Unit::withCount(['applications as users_count' => function ($query) {
        $query->where('status', 'Accepted');
    }])->get();
    return response()->json($units);
}



    /**
     * Store a newly created unit in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        // Create a new unit
        $unit = Unit::create($validated);

        return response()->json([
            'message' => 'Unit added successfully!',
            'unit' => $unit,
        ], 201);
    }

    /**
     * Display the specified unit.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Fetch a specific unit by ID
        $unit = Unit::findOrFail($id);

        return response()->json($unit);
    }

    /**
     * Update the specified unit in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Fetch the unit by ID
        $unit = Unit::findOrFail($id);

        // Validate incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'price' => 'required|numeric',
        ]);

        // Update the unit
        $unit->update($validated);

        return response()->json([
            'message' => 'Unit updated successfully!',
            'unit' => $unit,
        ]);
    }

    /**
     * Remove the specified unit from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
    
        // Clear related assignments
        User::where('unit_id', $id)->update(['unit_id' => null]);
    
        // Delete the unit
        $unit->delete();
    
        return response()->json(['message' => 'Unit deleted successfully!']);
    }
    
}
