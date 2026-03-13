<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
        $rules = [
            'payment_type' => [
                'required',
                Rule::in(['enrollment_fee', 'tuition_fee', 'exam_fee', 'document_fee', 'other'])
            ],
            'amount' => [
                'required',
                'numeric',
                'min:100',
                'max:10000000' // 10 millions FCFA max
            ],
            'payment_method' => [
                'required',
                Rule::in(['orange_money', 'mtn_money', 'bank_transfer', 'cash', 'check'])
            ]
        ];

        // Règles spécifiques selon la méthode de paiement
        switch ($this->input('payment_method')) {
            case 'orange_money':
            case 'mtn_money':
                $rules['phone_number'] = [
                    'required',
                    'string',
                    'regex:/^(\+237|237)?[67][0-9]{8}$/'
                ];
                $rules['amount'][] = 'max:1000000'; // 1 million FCFA max pour mobile money
                break;

            case 'bank_transfer':
                $rules['bank_name'] = [
                    'required',
                    'string',
                    'max:100',
                    Rule::in([
                        'Afriland First Bank',
                        'Ecobank Cameroun',
                        'UBA Cameroun',
                        'SGBC',
                        'Crédit Lyonnais',
                        'BICEC',
                        'CCA Bank',
                        'Autre'
                    ])
                ];
                $rules['account_number'] = [
                    'nullable',
                    'string',
                    'max:50',
                    'regex:/^[0-9\s\-]+$/'
                ];
                $rules['transaction_reference'] = [
                    'nullable',
                    'string',
                    'max:100',
                    'regex:/^[A-Z0-9\-_]+$/i'
                ];
                $rules['amount'][] = 'min:1000'; // 1000 FCFA min pour virement
                break;

            case 'check':
                $rules['check_number'] = [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^[0-9]+$/'
                ];
                $rules['bank_name'] = [
                    'required',
                    'string',
                    'max:100'
                ];
                $rules['check_date'] = [
                    'required',
                    'date',
                    'after_or_equal:today',
                    'before:' . now()->addDays(30)->format('Y-m-d')
                ];
                break;

            case 'cash':
                $rules['amount'][] = 'max:500000'; // 500k FCFA max en espèces
                $rules['payment_location'] = [
                    'required',
                    'string',
                    Rule::in(['yaounde_centre', 'yaounde_nlongkak', 'douala_akwa', 'douala_bonanjo'])
                ];
                break;
        }

        // Fichier de reçu requis pour certaines méthodes
        if (in_array($this->input('payment_method'), ['bank_transfer', 'check'])) {
            $rules['receipt_file'] = [
                'required',
                'file',
                'mimes:pdf,jpeg,png,jpg',
                'max:5120' // 5MB
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Le numéro de téléphone doit être au format camerounais (+237 6XX XXX XXX).',
            'amount.min' => 'Le montant minimum est de :min FCFA.',
            'amount.max' => 'Le montant maximum autorisé est de :max FCFA.',
            'account_number.regex' => 'Le numéro de compte ne peut contenir que des chiffres, espaces et tirets.',
            'transaction_reference.regex' => 'La référence ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'check_number.regex' => 'Le numéro de chèque ne peut contenir que des chiffres.',
            'check_date.after_or_equal' => 'La date du chèque ne peut pas être antérieure à aujourd\'hui.',
            'check_date.before' => 'La date du chèque ne peut pas être postérieure à 30 jours.',
            'receipt_file.required' => 'Le reçu de paiement est obligatoire pour cette méthode.',
            'receipt_file.mimes' => 'Le reçu doit être au format PDF, JPEG, PNG ou JPG.',
            'receipt_file.max' => 'Le fichier ne peut pas dépasser 5MB.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'payment_type' => 'type de paiement',
            'amount' => 'montant',
            'payment_method' => 'méthode de paiement',
            'phone_number' => 'numéro de téléphone',
            'bank_name' => 'nom de la banque',
            'account_number' => 'numéro de compte',
            'transaction_reference' => 'référence de transaction',
            'check_number' => 'numéro de chèque',
            'check_date' => 'date du chèque',
            'payment_location' => 'lieu de paiement',
            'receipt_file' => 'reçu de paiement'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérification de cohérence du montant selon le type
            $paymentType = $this->input('payment_type');
            $amount = $this->input('amount');

            if ($paymentType && $amount) {
                $expectedAmounts = [
                    'enrollment_fee' => [25000, 100000], // Entre 25k et 100k
                    'tuition_fee' => [50000, 500000],    // Entre 50k et 500k
                    'exam_fee' => [5000, 50000],         // Entre 5k et 50k
                    'document_fee' => [1000, 10000]      // Entre 1k et 10k
                ];

                if (isset($expectedAmounts[$paymentType])) {
                    [$min, $max] = $expectedAmounts[$paymentType];
                    if ($amount < $min || $amount > $max) {
                        $validator->errors()->add('amount', 
                            "Le montant pour {$paymentType} doit être entre {$min} et {$max} FCFA."
                        );
                    }
                }
            }

            // Vérification anti-fraude : montants suspects
            if ($amount && $amount > 1000000) {
                \Illuminate\Support\Facades\Log::warning('Tentative de paiement suspect', [
                    'user_id' => auth('sanctum')->id(),
                    'amount' => $amount,
                    'payment_method' => $this->input('payment_method'),
                    'ip' => request()->ip(),
                    'timestamp' => now()
                ]);
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log des tentatives de paiement invalides
        \Illuminate\Support\Facades\Log::warning('Tentative de paiement avec données invalides', [
            'user_id' => auth('sanctum')->id(),
            'payment_method' => $this->input('payment_method'),
            'amount' => $this->input('amount'),
            'ip' => request()->ip(),
            'errors' => array_keys($validator->errors()->toArray()),
            'timestamp' => now()
        ]);

        parent::failedValidation($validator);
    }
}