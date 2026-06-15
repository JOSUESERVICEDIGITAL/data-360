<?php

namespace App\Http\Controllers;

use App\Services\CoproprieteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CoproprieteController extends Controller
{
    public function __construct(protected CoproprieteService $service) {}

    /**
     * GET /api/coproprietes/rechercher?adresse=94 r des stations 59800 Lille
     */
    public function rechercher(Request $request): JsonResponse
    {
        $request->validate([
            'adresse'   => 'required|string|min:5|max:255',
            'page'      => 'sometimes|integer|min:1',
            'per_page'  => 'sometimes|integer|min:1|max:50',
        ]);

        $resultats = $this->service->rechercherParAdresse(
            adresse: $request->string('adresse'),
            page:    $request->integer('page', 1),
            perPage: $request->integer('per_page', 10),
        );

        return response()->json($resultats, $resultats['success'] ? 200 : 502);
    }

    /**
     * GET /api/coproprietes/{id}
     */
    public function detail(int $id): JsonResponse
    {
        $detail = $this->service->obtenirDetail($id);

        return response()->json($detail, $detail['success'] ? 200 : 502);
    }

    /**
     * GET /api/coproprietes/enrichir?adresse=94 r des stations 59800 Lille
     * Retourne directement le meilleur résultat pour un enrichissement rapide.
     */
    public function enrichir(Request $request): JsonResponse
    {
        $request->validate([
            'adresse' => 'required|string|min:5|max:255',
        ]);

        $data = $this->service->enrichirParAdresse($request->string('adresse'));

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune copropriété trouvée pour cette adresse.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * DELETE /api/coproprietes/cache  (superadmin only)
     */
    public function viderCache(Request $request): JsonResponse
    {
        $request->validate([
            'adresse' => 'sometimes|string',
            'id'      => 'sometimes|integer',
        ]);

        $this->service->viderCache(
            adresse: $request->input('adresse'),
            id:      $request->input('id'),
        );

        return response()->json(['success' => true, 'message' => 'Cache vidé.']);
    }
}