<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
class EventController extends Controller
{
    public function index()
    {
        $events = Event::whereNotNull('date')->get()->map(function ($event) {
            $event->date = Carbon::parse($event->date)
                           ->setTimezone('Asia/Manila')
                           ->toDateString();
            return $event;
        });
    
        return response()->json($events);
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event = Event::create($request->all());

        return response()->json(['message' => 'Event created successfully!', 'event' => $event]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $event->update($request->all());

        return response()->json(['message' => 'Event updated successfully!', 'event' => $event]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully!']);
    }
}
