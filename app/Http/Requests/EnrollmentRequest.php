<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Informations personnelles
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/' // Lettres, espaces, tirets, apostrophes
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('students', 'email')->ignore($this->user()->id ?? null, 'user_id')
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^(\+237|237)?[67][0-9]{8}$/' // Format camerounais
            ],
            'date_of_birth' => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(50)->format('Y-m-d') // Max 50 ans
            ],
            'place_of_birth' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'
            ],
            'gender' => [
                'required',
                Rule::in(['M', 'F'])
            ],
            'nationality' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-ZÀ-ÿ\s]+$/'
            ],
            'address' => [
                'required',
                'string',
                'min:10',
                'max:500'
            ],
            
            // Informations académiques
            'filiere_id' => [
                'required',
                'integer',
                'exists:filieres,id'
            ],
            'previous_education_level' => [
                'required',
                'string',
                Rule::in(['baccalaureat', 'licence', 'master', 'autre'])
            ],
            'previous_institution' => [
                'required',
                'string',
                'min:3',
                'max:200'
            ],
            'graduation_year' => [
                'required',
                'integer',
                'min:' . (date('Y') - 30),
                'max:' . date('Y')
            ],
            'average_grade' => [
                'required',
                'numeric',
                'min:0',
                'max:20'
            ],
            
            // Contact d'urgence
            'emergency_contact_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/'
            ],
            'emergency_contact_phone' => [
                'required',
                'string',
                'regex:/^(\+237|237)?[67][0-9]{8}$/'
            ],
            'emergency_contact_relationship' => [
                'required',
                'string',
                Rule::in(['parent', 'conjoint', 'frere_soeur', 'ami', 'tuteur', 'autre'])
            ],
            
            // Fichiers
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048', // 2MB
                'dimensions:min_width=300,min_height=400,max_width=1000,max_height=1200'
            ],
            'documents.*' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,png,jpg',
                'max:5120' // 5MB
            ],
            
            // Centre de dépôt préféré
            'preferred_submission_center_id' => [
                'nullable',
                'integer',
                'exists:document_submission_centers,id'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            'last_name.regex' => 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            'phone.regex' => 'Le numéro de téléphone doit être au format camerounais (+237 6XX XXX XXX).',
            'emergency_contact_phone.regex' => 'Le numéro d\'urgence doit être au format camerounais.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'date_of_birth.after' => 'L\'âge maximum autorisé est de 50 ans.',
            'email.email' => 'L\'adresse email doit être valide et vérifiable.',
            'profile_photo.dimensions' => 'La photo doit avoir une taille minimale de 300x400 pixels.',
            'average_grade.min' => 'La moyenne doit être comprise entre 0 et 20.',
            'average_grade.max' => 'La moyenne doit être comprise entre 0 et 20.',
            'graduation_year.min' => 'L\'année d\'obtention ne peut pas être antérieure à ' . (date('Y') - 30) . '.',
            'graduation_year.max' => 'L\'année d\'obtention ne peut pas être postérieure à ' . date('Y') . '.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'adresse email',
            'phone' => 'numéro de téléphone',
            'date_of_birth' => 'date de naissance',
            'place_of_birth' => 'lieu de naissance',
            'gender' => 'sexe',
            'nationality' => 'nationalité',
            'address' => 'adresse',
            'filiere_id' => 'filière',
            'previous_education_level' => 'niveau d\'études précédent',
            'previous_institution' => 'établissement précédent',
            'graduation_year' => 'année d\'obtention',
            'average_grade' => 'moyenne générale',
            'emergency_contact_name' => 'nom du contact d\'urgence',
            'emergency_contact_phone' => 'téléphone du contact d\'urgence',
            'emergency_contact_relationship' => 'relation avec le contact d\'urgence',
            'profile_photo' => 'photo de profil',
            'preferred_submission_center_id' => 'centre de dépôt préféré'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->toArray();
        
        // Log des tentatives de validation échouées pour sécurité
        \Illuminate\Support\Facades\Log::warning('Tentative d\'inscription avec données invalides', [
            'user_id' => auth('sanctum')->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'errors' => array_keys($errors),
            'timestamp' => now()
        ]);

        parent::failedValidation($validator);
    }
}