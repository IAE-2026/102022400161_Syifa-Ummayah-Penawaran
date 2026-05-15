<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class BidController extends Controller
{
    #[OA\Get(
        path: "/api/v1/bids",
        summary: "Ambil semua penawaran",
        security: [["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $bids = Bid::all();

        return response()->json([
            'status' => 'success',
            'data' => $bids
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/bids/{id}",
        summary: "Ambil detail penawaran",
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show($id)
    {
        $bid = Bid::find($id);

        if (!$bid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $bid
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/bids",
        summary: "Kirim penawaran baru",
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["bidder_id", "item_id", "bid_amount"],
                properties: [
                    new OA\Property(property: "bidder_id", type: "string", example: "USER123"),
                    new OA\Property(property: "item_id", type: "string", example: "ITEM001"),
                    new OA\Property(property: "bid_amount", type: "number", example: 500000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'bidder_id' => 'required',
            'item_id' => 'required',
            'bid_amount' => 'required|numeric',
        ]);

        try {
            $userCheck = Http::get("http://localhost:8004/api/v1/verifications/" . $request->bidder_id);

            $itemCheck = Http::get("http://localhost:8001/api/v1/items/" . $request->item_id);

            if (!$userCheck->successful() || !$itemCheck->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi ke service lain gagal'
                ], 403);
            }

        } catch (\Exception $e) {

        }

        $bid = Bid::create([
            'bidder_id' => $request->bidder_id,
            'item_id' => $request->item_id,
            'bid_amount' => $request->bid_amount,
            'status' => 'valid'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $bid
        ], 201);
    }
}