<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Distance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class DistanceController extends Controller
{
   // Calculate distance
    public function calculateDistance(Request $request)
{
   // Verify the validity of the entered data
    $validator = Validator::make($request->all(), [
        'points' => 'required|array',
        'points.*' => 'required|array|size:2',
        'line_name' => 'required|string',
        'correction' => 'required|array',
        'correction.*' => 'required|array|size:2',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // points it is the Stations
    // correctio, is the  Road path
    $points = $request->input('points');
    $line_name = $request->input('line_name');
    $correction = $request->input('correction');

    $url = "https://api.openrouteservice.org/v2/directions/driving-hgv/geojson";
    $apiKey = '5b3ce3597851110001cf624849bb1fc5d8d64f709d253921abe15e0d';
    $body = [
        'coordinates' => $correction,
        'units' => 'km'
    ];

    try {
        $response = Http::withHeaders([
            'Accept' => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
            'Authorization' => $apiKey,
            'Content-Type' => 'application/json; charset=utf-8'
        ])->post($url, $body);

        if ($response->failed()) {
            throw new \Exception('Failed to connect to OpenRouteService');
        }

        $data = $response->json();
        if (!isset($data['features']) || count($data['features']) === 0) {
            return response()->json(['error' => 'The path cannot be calculated for these points.'], 400);
        }

        $feature = $data['features'][0];
        if (!isset($feature['properties']['summary']['distance'])) {
            return response()->json(['error' => 'Distance information is missing in the API response.'], 500);
        }

        $distance = $feature['properties']['summary']['distance'];
        $geometry = $feature['geometry'];

        // Save to database
        $distanceRecord = Distance::create([
            'points' => json_encode($points),
            'distance' => $distance,
            'line_name' => $line_name,
            'geometry' => json_encode($geometry),
            'correction' => json_encode($correction),
        ]);

        return response()->json(['status' => 'success', 'data' => $distanceRecord], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred while calculating the distance: ' . $e->getMessage()], 500);
    }
}


    // get all paths
    function getDistances()
    {
        $distances = Distance::all();
        return response()->json($distances, 200);
    }

    // get a specific path
    function getDistance($id)
    {
        $distance = Distance::find($id);
        if (!$distance) {
            return response()->json(['errors' => 'Distance not found'], 404);
        }
        return response()->json($distance, 200);
    }

    // Update distance
public function updateDistance(Request $request, $id)
{
    $distanceRecord = Distance::find($id);
    if (!$distanceRecord) {
        return response()->json(['errors' => 'Distance not found.'], 404);
    }

    $validator = Validator::make($request->all(), [
        'points' => 'required|array',
        'points.*' => 'required|array|size:2',
        'line_name' => 'required|string',
        'correction' => 'required|array',
        'correction.*' => 'required|array|size:2',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $points = $request->input('points');
    $line_name = $request->input('line_name');
    $correction = $request->input('correction');

    $url = "https://api.openrouteservice.org/v2/directions/driving-hgv/geojson";
    $apiKey = '5b3ce3597851110001cf624849bb1fc5d8d64f709d253921abe15e0d';
    $body = [
        'coordinates' => $correction,
        'units' => 'km'
    ];

    try {
        $response = Http::withHeaders([
            'Accept' => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
            'Authorization' => $apiKey,
            'Content-Type' => 'application/json; charset=utf-8'
        ])->post($url, $body);

        if ($response->failed()) {
            throw new \Exception('Failed to connect to OpenRouteService');
        }

        $data = $response->json();
        if (!isset($data['features']) || count($data['features']) === 0) {
            return response()->json(['error' => 'The path cannot be calculated for these points.'], 400);
        }

        $feature = $data['features'][0];
        if (!isset($feature['properties']['summary']['distance'])) {
            return response()->json(['error' => 'Distance information is missing in the API response.'], 500);
        }

        $calculatedDistance = $feature['properties']['summary']['distance'];
        $geometry = $feature['geometry'];

        // Update data in the database
        $distanceRecord->update([
            'points' => json_encode($points),
            'distance' => $calculatedDistance,
            'line_name' => $line_name,
            'geometry' => json_encode($geometry),
            'correction' => json_encode($correction),
        ]);

        return response()->json(['status' => 'Data updated successfully.', 'distance' => $distanceRecord], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred while calculating the distance: ' . $e->getMessage()], 500);
    }
}



   // Delete a space
    function deleteDistance($id)
    {
        $distance = Distance::find($id);
        if ($distance) {
            $distance->delete();
            return response()->json(['status' => 'Distance deleted successfully.'], 200);
        } else {
            return response()->json(['errors' => 'Distance not found.'], 404);
        }
    }

    //Display the distance on the map
    public function showDistance($id)
    {
        $distance = Distance::find($id);
        if (!$distance) {
            return "Please enter a valid ID";
        }
        return view('show_distance', compact('distance'));
    }

    // Display the map
    public function showMap()
    {
        $distances = Distance::all();
        return view('show_Map', compact('distances'));
    }
}
