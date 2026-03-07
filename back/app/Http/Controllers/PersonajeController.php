<?php

namespace App\Http\Controllers;

use App\Models\Personaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonajeController extends Controller
{
    private const ESTILOS = [
        'estilo_alegre' => 'Alegre / Feliz: sonrisa abierta y ojos brillantes.',
        'estilo_celebrando' => 'Celebrando / Exito: saltando con confeti y brazos arriba.',
        'estilo_pensando' => 'Pensando: ceja levantada y mano en la boca.',
        'estilo_confundido' => 'Confundido: ojos pequenos y boca torcida.',
        'estilo_triste' => 'Triste: ojos caidos y boca hacia abajo.',
        'estilo_motivado' => 'Motivador serio: expresion intensa de "Vamos!".',
        'estilo_cansado' => 'Cansado / Descuidado: ojos medio cerrados, energia baja.',
        'estilo_tierno' => 'Tiernito / Suplicando practica: gesto adorable para motivar.',
    ];

    public function index(): JsonResponse
    {
        $personajes = Personaje::orderByDesc('id')->get();

        return response()->json($personajes);
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        $this->validateStyleInputs($request);

        $personaje = Personaje::create([
            'nombre' => $validatedData['nombre'],
        ]);

        $this->syncImagenes($request, $personaje);

        return response()->json($personaje->fresh(), 201);
    }

    public function update(Request $request, Personaje $personaje): JsonResponse
    {
        $validatedData = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
        ]);
        $this->validateStyleInputs($request);

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

    public function generarEstilosIa(Request $request): JsonResponse
    {
        $request->validate([
            'imagen_base' => 'required|image|max:8192',
            'nombre' => 'nullable|string|max:255',
        ]);

        $apiKey = (string) config('services.google_ai_studio.api_key');
        $model = (string) config('services.google_ai_studio.image_model', 'gemini-2.5-flash-image-preview');

        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'api_key' => ['Falta configurar GOOGLE_AI_STUDIO_API_KEY en el backend.'],
            ]);
        }

        $baseFile = $request->file('imagen_base');
        $baseMime = $baseFile->getMimeType() ?: 'image/png';
        $base64 = base64_encode((string) file_get_contents($baseFile->getPathname()));
        $nombre = trim((string) $request->input('nombre', 'personaje'));
        $generated = [];

        foreach (self::ESTILOS as $styleKey => $styleDescription) {
            $prompt = $this->buildPrompt($nombre, $styleDescription);
            $generated[$styleKey] = $this->generateStyleImageWithGemini(
                apiKey: $apiKey,
                model: $model,
                prompt: $prompt,
                sourceBase64: $base64,
                sourceMime: $baseMime,
                styleKey: $styleKey,
                nombre: $nombre,
            );
        }

        return response()->json([
            'message' => 'Estilos generados con IA',
            'imagenes' => $generated,
        ]);
    }

    public function listarModelosIa(): JsonResponse
    {
        $apiKey = (string) config('services.google_ai_studio.api_key');
        if ($apiKey === '') {
            throw ValidationException::withMessages([
                'api_key' => ['Falta configurar GOOGLE_AI_STUDIO_API_KEY en el backend.'],
            ]);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'ia' => ['No se pudo listar modelos IA: ' . $response->body()],
            ]);
        }

        $models = $response->json('models', []);

        return response()->json([
            'models' => array_map(function ($model) {
                return [
                    'name' => $model['name'] ?? null,
                    'displayName' => $model['displayName'] ?? null,
                    'supportedGenerationMethods' => $model['supportedGenerationMethods'] ?? [],
                ];
            }, $models),
        ]);
    }

    private function syncImagenes(Request $request, Personaje $personaje): void
    {
        foreach (array_keys(self::ESTILOS) as $estilo) {
            if ($request->hasFile($estilo)) {
                $oldFile = $personaje->{$estilo};
                $newFile = $this->compressAndSaveImage($request->file($estilo));

                $personaje->{$estilo} = $newFile;
                $personaje->save();

                if ($oldFile && $oldFile !== $newFile) {
                    $this->deleteImageIfExists($oldFile);
                }

                continue;
            }

            $requestedValue = $request->input($estilo);
            if (!is_string($requestedValue) || trim($requestedValue) === '') {
                continue;
            }

            $sanitized = $this->sanitizeImageFilename($requestedValue);
            if ($sanitized === '' || !is_file(public_path('images/' . $sanitized))) {
                continue;
            }

            $personaje->{$estilo} = $sanitized;
            $personaje->save();
        }
    }

    private function compressAndSaveImage($file): string
    {
        $binary = (string) file_get_contents($file->getPathname());

        return $this->saveTransparentPngFromBinary(
            binary: $binary,
            filenamePrefix: time() . '_' . Str::random(10),
        );
    }

    private function validateStyleInputs(Request $request): void
    {
        $errors = [];

        foreach (array_keys(self::ESTILOS) as $estilo) {
            if ($request->hasFile($estilo)) {
                $file = $request->file($estilo);
                $mime = (string) $file->getMimeType();

                if (!str_starts_with($mime, 'image/')) {
                    $errors[$estilo][] = 'El archivo debe ser una imagen.';
                }
                if ($file->getSize() > (5 * 1024 * 1024)) {
                    $errors[$estilo][] = 'La imagen no debe superar 5MB.';
                }
                continue;
            }

            if ($request->filled($estilo)) {
                $value = (string) $request->input($estilo);
                if (mb_strlen($value) > 255) {
                    $errors[$estilo][] = 'Nombre de imagen demasiado largo.';
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function sanitizeImageFilename(string $value): string
    {
        return trim(basename($value));
    }

    private function buildPrompt(string $nombre, string $styleDescription): string
    {
        return "Transforma la imagen de referencia al estilo de mascota educativa 2D tipo app de idiomas, "
            . "colores vibrantes, contorno limpio, fondo transparente y mismo personaje (misma identidad visual). "
            . "Genera una sola imagen cuadrada 1:1. Estado: {$styleDescription} "
            . "Personaje: {$nombre}.";
    }

    private function generateStyleImageWithGemini(
        string $apiKey,
        string $model,
        string $prompt,
        string $sourceBase64,
        string $sourceMime,
        string $styleKey,
        string $nombre
    ): string {
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inlineData' => ['mimeType' => $sourceMime, 'data' => $sourceBase64]],
                ],
            ]],
            'generationConfig' => [
                'responseModalities' => ['IMAGE', 'TEXT'],
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $response = Http::timeout(120)->post($url, $payload);

        if (!$response->successful() && str_contains($response->body(), 'inlineData')) {
            $payload['contents'][0]['parts'][1] = [
                'inline_data' => [
                    'mime_type' => $sourceMime,
                    'data' => $sourceBase64,
                ],
            ];
            $response = Http::timeout(120)->post($url, $payload);
        }

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'ia' => ['No se pudo generar la imagen IA: ' . $response->body()],
            ]);
        }

        [$imageData, $mimeType] = $this->extractImageFromGeminiResponse($response->json());
        if ($imageData === '') {
            throw ValidationException::withMessages([
                'ia' => ['La IA no devolvió imagen para el estado ' . $styleKey],
            ]);
        }

        $binary = base64_decode($imageData, true);
        if ($binary === false) {
            throw ValidationException::withMessages([
                'ia' => ['No se pudo decodificar imagen IA para ' . $styleKey],
            ]);
        }

        return $this->saveGeneratedImage($binary, $styleKey, $nombre);
    }

    private function extractImageFromGeminiResponse(array $json): array
    {
        $candidates = $json['candidates'] ?? [];
        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            foreach ($parts as $part) {
                $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;
                if (!is_array($inlineData)) {
                    continue;
                }

                $data = (string) ($inlineData['data'] ?? '');
                $mime = (string) ($inlineData['mimeType'] ?? $inlineData['mime_type'] ?? 'image/png');
                if ($data !== '') {
                    return [$data, $mime];
                }
            }
        }

        return ['', ''];
    }

    private function saveGeneratedImage(string $binary, string $styleKey, string $nombre): string
    {
        $slug = Str::slug($nombre ?: 'personaje');
        $prefix = "{$slug}_{$styleKey}_" . time() . '_' . Str::lower(Str::random(6));

        return $this->saveTransparentPngFromBinary(
            binary: $binary,
            filenamePrefix: $prefix,
        );
    }

    private function saveTransparentPngFromBinary(string $binary, string $filenamePrefix): string
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw ValidationException::withMessages([
                'imagen' => ['No se pudo procesar la imagen enviada.'],
            ]);
        }

        $scaled = $this->scaleDownGdImage($image, 800, 800);
        if ($scaled !== $image) {
            imagedestroy($image);
            $image = $scaled;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $saturation = $max === 0 ? 0 : ($max - $min) / $max;
                $whiteness = ($r + $g + $b) / 3;

                $targetAlpha = $alpha;
                if ($whiteness >= 245) {
                    $targetAlpha = 127;
                } elseif ($whiteness >= 225 && $saturation <= 0.12) {
                    $fade = ($whiteness - 225) / 20;
                    $fadeAlpha = (int) round(127 * $fade);
                    $targetAlpha = max($alpha, $fadeAlpha);
                }

                if ($targetAlpha !== $alpha) {
                    $color = (($targetAlpha & 0x7F) << 24) | (($r & 0xFF) << 16) | (($g & 0xFF) << 8) | ($b & 0xFF);
                    imagesetpixel($image, $x, $y, $color);
                }
            }
        }

        $filename = $filenamePrefix . '.png';
        $path = public_path('images/' . $filename);

        imagepng($image, $path, 6);
        imagedestroy($image);

        return $filename;
    }

    private function scaleDownGdImage(\GdImage $image, int $maxWidth, int $maxHeight): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $image;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $scaled = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefill($scaled, 0, 0, $transparent);

        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $scaled;
    }

    private function deleteImageIfExists(string $filename): void
    {
        $path = public_path('images/' . $filename);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
