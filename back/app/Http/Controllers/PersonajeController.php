<?php

namespace App\Http\Controllers;

use App\Models\Personaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class PersonajeController extends Controller
{
    private const ESTILOS = [
        'estilo_alegre',
        'estilo_pensando',
        'estilo_confundido',
        'estilo_celebrando',
        'estilo_triste',
        'estilo_motivado',
        'estilo_cansado',
        'estilo_tierno',
    ];

    public function index(): JsonResponse
    {
        $personajes = Personaje::orderByDesc('id')->get();

        return response()->json($personajes);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate($this->rules(false));

        $personaje = Personaje::create([
            'nombre' => $validatedData['nombre'],
        ]);

        $this->syncImagenes($request, $personaje);

        return response()->json($personaje->fresh(), 201);
    }

    public function update(Request $request, Personaje $personaje): JsonResponse
    {
        $validatedData = $request->validate($this->rules(true));

        if (array_key_exists('nombre', $validatedData)) {
            $personaje->nombre = $validatedData['nombre'];
            $personaje->save();
        }

        $this->syncImagenes($request, $personaje);

        return response()->json($personaje->fresh());
    }

    public function destroy(Personaje $personaje): JsonResponse
    {
        $personaje->delete();

        return response()->json(['message' => 'Personaje eliminado']);
    }

    private function rules(bool $isUpdate): array
    {
        $rules = [
            'nombre' => $isUpdate
                ? 'sometimes|required|string|max:255'
                : 'required|string|max:255',
        ];

        foreach (self::ESTILOS as $estilo) {
            $rules[$estilo] = 'nullable|image|max:5120';
        }

        return $rules;
    }

    private function syncImagenes(Request $request, Personaje $personaje): void
    {
        foreach (self::ESTILOS as $estilo) {
            if (!$request->hasFile($estilo)) {
                continue;
            }

            $oldFile = $personaje->{$estilo};
            $newFile = $this->compressAndSaveImage($request->file($estilo));

            $personaje->{$estilo} = $newFile;
            $personaje->save();

            if ($oldFile) {
                $this->deleteImageIfExists($oldFile);
            }
        }
    }

    private function compressAndSaveImage($file): string
    {
        $filename = time() . '_' . Str::random(10) . '.jpg';
        $path = public_path('images/' . $filename);

        $manager = new ImageManager(new Driver());
        $manager->read($file->getPathname())
            ->scaleDown(width: 800, height: 800)
            ->toJpeg(75)
            ->save($path);

        return $filename;
    }

    private function deleteImageIfExists(string $filename): void
    {
        $path = public_path('images/' . $filename);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
