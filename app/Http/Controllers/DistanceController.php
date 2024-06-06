<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Distance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class DistanceController extends Controller
{


    // calc distance
    public function calculateDistance(Request $request)
    {
        // Verify the validity of the entered data
        $validator = Validator::make($request->all(), [
            'points' => 'required|array',
            'points.*' => 'required|array|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $points = $request->input('points');

        $url = "https://api.openrouteservice.org/v2/directions/driving-hgv/geojson";
        $apiKey = '5b3ce3597851110001cf624849bb1fc5d8d64f709d253921abe15e0d';
        $body = [
            'coordinates' => $points,
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

            // save to database
            $distanceRecord = Distance::create([
                'points' => json_encode($points),
                'distance' => $distance,
                'geometry' => json_encode($geometry),
            ]);

            return response()->json(['status' => 'success', 'data' => $distanceRecord], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while calculating the distance: ' . $e->getMessage()], 500);
        }
    }


    // Get Distances
    function GetDistances()
    {
        $userDistance = Distance::All();
        return response()->json($userDistance, 200);
    }


    // get Distance
    function getDistance($id)
    {
        $userDistance = Distance::find($id);
        if (!$userDistance) {
            return response()->json(['errors' => 'check your id , Distance not found'], 404);
        }
        return response()->json($userDistance, 200);
    }


    // update Distance
    public function updateDistance(Request $request, $id)
    {
        $userDistance = Distance::find($id);
        if (!$userDistance) {
            return response()->json(['errors' => 'Distance not found.'], 404);
        }
        $validator = Validator::make($request->all(), [
            'points' => 'required|array',
            'points.*' => 'required|array|size:2'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $points = $request->input('points');

        $url = "https://api.openrouteservice.org/v2/directions/driving-hgv/geojson";
        $apiKey = '5b3ce3597851110001cf624849bb1fc5d8d64f709d253921abe15e0d';
        $body = [
            'coordinates' => $points,
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


            // Update data in the database
            $userDistance->update([
                'points' => json_encode($points),
                'distance' => $distance,
                'geometry' => json_encode($geometry),
            ]);
            return response()->json([
                'status' => 'Data updated successfully.',
                'distance' => $userDistance
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while calculating the distance: ' . $e->getMessage()], 500);
        }
    }










    // delete Distance

    function deleteDistance($id)
    {
        if ($userDistance = Distance::find($id)) {
            $userDistance->delete();
            return response()->json(['status' => 'Distance deleted successfully.'], 200);
        } else {
            return response()->json(['errors' => 'Distance not found.'], 404);
        }
    }





    // View the distance on the page (my map)
    public function showDistance($id)
    {
        $distance = Distance::find($id);
        if (!$distance) {
            return "Please enter a valid ID";
        }
        return view('show_distance', compact('distance'));
    }
}
