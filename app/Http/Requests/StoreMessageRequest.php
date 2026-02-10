<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get validation rules that apply to request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'type' => ['nullable', 'in:text,image,file,audio,video,system'],
            'file' => ['nullable', 'file'],
            'file_url' => ['nullable', 'url'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:600'], // Durée max 10 minutes (600 secondes)
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu du message est requis',
            'content.max' => 'Le message ne peut pas dépasser 10 000 caractères',
            'type.in' => 'Le type de message doit être: text, image, file, audio, video ou system',
            'file.mimes' => 'Le fichier doit être une image (jpeg, jpg, png, gif, webp) ou un fichier audio (mp3, wav, ogg, m4a)',
            'file.max' => 'Le fichier ne doit pas dépasser 5Mo',
            'duration.max' => 'La durée du message vocal ne doit pas dépasser 5 minutes',
        ];
    }

    /**
     * Configure the validator instance with custom validation.
     *
     * Cette méthode ajoute des validations personnalisées pour bloquer les liens
     * et les numéros de téléphone, même si le frontend est contourné.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $content = $this->input('content');

            // Vérifier seulement si le contenu existe et n'est pas vide
            if (empty($content)) {
                return;
            }

            $contentTrimmed = trim($content);

            Log::info('[StoreMessageRequest] Validation check', [
                'content_length' => strlen($contentTrimmed),
                'has_content' => !empty($contentTrimmed),
            ]);

            // Validation des liens - BLOQUE toute requête contenant un lien
            if ($this->containsLinks($contentTrimmed)) {
                Log::warning('[StoreMessageRequest] Links detected - BLOCKING REQUEST', [
                    'content_preview' => substr($contentTrimmed, 0, 100),
                ]);
                $validator->errors()->add('content', 'Les liens sont interdits dans les messages');
                return; // Arrêter immédiatement la validation
            }

            // Validation des numéros de téléphone - BLOQUE toute requête contenant un numéro
            if ($this->containsPhoneNumbers($contentTrimmed)) {
                Log::warning('[StoreMessageRequest] Phone numbers detected - BLOCKING REQUEST', [
                    'content_preview' => substr($contentTrimmed, 0, 100),
                ]);
                $validator->errors()->add('content', 'Les numéros de téléphone sont interdits dans les messages');
                return; // Arrêter immédiatement la validation
            }

            Log::info('[StoreMessageRequest] Validation passed successfully');
        });
    }

    /**
     * Vérifie si le contenu contient des liens
     *
     * Regex équivalente à celle du frontend JavaScript.
     * Détecte: http://, https://, www., et domaines (.com, .org, etc.)
     *
     * @param string $content
     * @return bool
     */
    protected function containsLinks(string $content): bool
    {
        // Pattern pour détecter les liens (http://, https://, www., domaines)
        $linkPatterns = [
            // Protocoles http/https
            '/(https?:\/\/)|(www\.)/i',
            // Domaines avec TLD (ex: example.com, example.org)
            '/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/[^\s]*)?/i',
        ];

        foreach ($linkPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si le contenu contient des numéros de téléphone
     *
     * Regex équivalente à celle du frontend JavaScript.
     * Détecte les formats locaux et internationaux.
     *
     * @param string $content
     * @return bool
     */
    protected function containsPhoneNumbers(string $content): bool
    {
        // Patterns pour détecter les numéros de téléphone
        $phonePatterns = [
            // Format international: +33 6 12 34 56 78
            '/\+?\d{1,3}[\s\-\.\(\)]*\d{2,3}[\s\-\.\(\)]*\d{2,3}[\s\-\.\(\)]*\d{2}[\s\-\.\(\)]*\d{2}/',
            // Format français: 06 12 34 56 78 ou 0612345678
            '/0[1-9](?:[\s\-\.\.]?\d{2}){4}/',
            // Format US/UK: (555) 123-4567
            '/\(\d{3}\)\s*\d{3}[-\s]\d{4}/',
            // Format simple: 10-11 chiffres consécutifs
            '/(?<!\d)\d{10,11}(?!\d)/',
            // Format avec espaces: 6 12 34 56 78
            '/\d(?:[\s\-\.\.]?\d){9,10}/',
        ];

        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
