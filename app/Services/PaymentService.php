<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Traiter un paiement Orange Money
     */
    public function processOrangeMoneyPayment($studentId, $amount, $phoneNumber, $paymentType = 'tuition_fee')
    {
        try {
            $student = Student::findOrFail($studentId);
            
            // Configuration Orange Money API (sandbox)
            $orangeConfig = [
                'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com/orange-money-webpay/dev/v1'),
                'merchant_key' => env('ORANGE_MONEY_MERCHANT_KEY'),
                'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
                'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
                'return_url' => env('APP_URL') . '/api/payments/orange/callback',
                'cancel_url' => env('APP_URL') . '/api/payments/orange/cancel',
                'notif_url' => env('APP_URL') . '/api/payments/orange/notify'
            ];

            // Générer un token d'accès
            $tokenResponse = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($orangeConfig['client_id'] . ':' . $orangeConfig['client_secret']),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->post($orangeConfig['base_url'] . '/oauth/token', [
                'grant_type' => 'client_credentials'
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Erreur authentification Orange Money: ' . $tokenResponse->body());
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'payment_type' => $paymentType,
                'amount' => $amount,
                'payment_method' => 'orange_money',
                'reference_number' => Payment::generateReferenceNumber(),
                'status' => 'pending',
                'payment_date' => now(),
                'academic_year' => $student->academic_year,
                'mobile_number' => $phoneNumber,
                'notes' => 'Paiement Orange Money en cours'
            ]);

            // Initier le paiement Orange Money
            $paymentData = [
                'merchant_key' => $orangeConfig['merchant_key'],
                'currency' => 'XAF',
                'order_id' => $payment->reference_number,
                'amount' => $amount,
                'return_url' => $orangeConfig['return_url'],
                'cancel_url' => $orangeConfig['cancel_url'],
                'notif_url' => $orangeConfig['notif_url'],
                'lang' => 'fr',
                'reference' => 'SGEE-' . $student->student_number . '-' . $payment->id
            ];

            $paymentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($orangeConfig['base_url'] . '/webpayment', $paymentData);

            if (!$paymentResponse->successful()) {
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Erreur Orange Money: ' . $paymentResponse->body()
                ]);
                throw new \Exception('Erreur initiation paiement Orange Money: ' . $paymentResponse->body());
            }

            $responseData = $paymentResponse->json();
            
            // Mettre à jour le paiement avec les informations Orange Money
            $payment->update([
                'transaction_id' => $responseData['pay_token'] ?? null,
                'gateway_response' => json_encode($responseData),
                'payment_url' => $responseData['payment_url'] ?? null
            ]);

            Log::info('Paiement Orange Money initié', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'amount' => $amount,
                'pay_token' => $responseData['pay_token'] ?? null
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'payment_url' => $responseData['payment_url'] ?? null,
                'pay_token' => $responseData['pay_token'] ?? null,
                'message' => 'Paiement Orange Money initié avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur paiement Orange Money', [
                'student_id' => $studentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement Orange Money: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un paiement MTN Money
     */
    public function processMtnMoneyPayment($studentId, $amount, $phoneNumber, $paymentType = 'tuition_fee')
    {
        try {
            $student = Student::findOrFail($studentId);
            
            // Configuration MTN Money API (sandbox)
            $mtnConfig = [
                'base_url' => env('MTN_MONEY_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
                'subscription_key' => env('MTN_MONEY_SUBSCRIPTION_KEY'),
                'api_user' => env('MTN_MONEY_API_USER'),
                'api_key' => env('MTN_MONEY_API_KEY'),
                'target_environment' => env('MTN_MONEY_TARGET_ENV', 'sandbox'),
                'callback_url' => env('APP_URL') . '/api/payments/mtn/callback'
            ];

            // Générer un token d'accès MTN
            $tokenResponse = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $mtnConfig['subscription_key'],
                'Authorization' => 'Basic ' . base64_encode($mtnConfig['api_user'] . ':' . $mtnConfig['api_key'])
            ])->post($mtnConfig['base_url'] . '/collection/token/', []);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Erreur authentification MTN Money: ' . $tokenResponse->body());
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'payment_type' => $paymentType,
                'amount' => $amount,
                'payment_method' => 'mtn_money',
                'reference_number' => Payment::generateReferenceNumber(),
                'status' => 'pending',
                'payment_date' => now(),
                'academic_year' => $student->academic_year,
                'mobile_number' => $phoneNumber,
                'notes' => 'Paiement MTN Money en cours'
            ]);

            // Générer un UUID pour la transaction MTN
            $transactionId = \Illuminate\Support\Str::uuid()->toString();

            // Initier le paiement MTN Money
            $paymentData = [
                'amount' => (string) $amount,
                'currency' => 'EUR', // MTN sandbox utilise EUR, en production ce sera XAF
                'externalId' => $payment->reference_number,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $phoneNumber
                ],
                'payerMessage' => 'Paiement frais scolaires SGEE',
                'payeeNote' => 'SGEE - ' . $student->student_number . ' - ' . $paymentType
            ];

            $paymentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Reference-Id' => $transactionId,
                'X-Target-Environment' => $mtnConfig['target_environment'],
                'Ocp-Apim-Subscription-Key' => $mtnConfig['subscription_key'],
                'Content-Type' => 'application/json'
            ])->post($mtnConfig['base_url'] . '/collection/v1_0/requesttopay', $paymentData);

            if (!$paymentResponse->successful()) {
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Erreur MTN Money: ' . $paymentResponse->body()
                ]);
                throw new \Exception('Erreur initiation paiement MTN Money: ' . $paymentResponse->body());
            }

            // Mettre à jour le paiement avec les informations MTN
            $payment->update([
                'transaction_id' => $transactionId,
                'gateway_response' => json_encode([
                    'mtn_transaction_id' => $transactionId,
                    'phone_number' => $phoneNumber,
                    'status' => 'PENDING'
                ])
            ]);

            Log::info('Paiement MTN Money initié', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'amount' => $amount,
                'mtn_transaction_id' => $transactionId
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'message' => 'Paiement MTN Money initié avec succès. Veuillez confirmer sur votre téléphone.'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur paiement MTN Money', [
                'student_id' => $studentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement MTN Money: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un paiement bancaire
     */
    public function processBankPayment($studentId, $amount, $bankDetails, $paymentType = 'tuition_fee')
    {
        try {
            $student = Student::findOrFail($studentId);

            // Créer l'enregistrement de paiement bancaire
            $payment = Payment::create([
                'student_id' => $student->id,
                'user_id' => $student->user_id,
                'payment_type' => $paymentType,
                'amount' => $amount,
                'payment_method' => 'bank_transfer',
                'reference_number' => Payment::generateReferenceNumber(),
                'status' => 'pending',
                'payment_date' => now(),
                'academic_year' => $student->academic_year,
                'bank_name' => $bankDetails['bank_name'] ?? null,
                'account_number' => $bankDetails['account_number'] ?? null,
                'transaction_reference' => $bankDetails['transaction_reference'] ?? null,
                'notes' => 'Paiement bancaire - Validation manuelle requise'
            ]);

            Log::info('Paiement bancaire enregistré', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'amount' => $amount,
                'bank_name' => $bankDetails['bank_name'] ?? null
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'message' => 'Paiement bancaire enregistré. Il sera validé après vérification.'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur paiement bancaire', [
                'student_id' => $studentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement bancaire: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement Orange Money
     */
    public function checkOrangeMoneyStatus($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);
            
            if ($payment->payment_method !== 'orange_money') {
                throw new \Exception('Ce paiement n\'est pas un paiement Orange Money');
            }

            $orangeConfig = [
                'base_url' => env('ORANGE_MONEY_BASE_URL', 'https://api.orange.com/orange-money-webpay/dev/v1'),
                'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
                'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET')
            ];

            // Obtenir un nouveau token
            $tokenResponse = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($orangeConfig['client_id'] . ':' . $orangeConfig['client_secret']),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])->post($orangeConfig['base_url'] . '/oauth/token', [
                'grant_type' => 'client_credentials'
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Erreur authentification Orange Money');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Vérifier le statut du paiement
            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get($orangeConfig['base_url'] . '/webpayment/' . $payment->transaction_id);

            if ($statusResponse->successful()) {
                $statusData = $statusResponse->json();
                
                if ($statusData['status'] === 'SUCCESS') {
                    $payment->update([
                        'status' => 'validated',
                        'validated_at' => now(),
                        'notes' => 'Paiement Orange Money confirmé automatiquement'
                    ]);
                } elseif ($statusData['status'] === 'FAILED') {
                    $payment->update([
                        'status' => 'failed',
                        'notes' => 'Paiement Orange Money échoué: ' . ($statusData['message'] ?? 'Erreur inconnue')
                    ]);
                }
            }

            return $payment->fresh();

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut Orange Money', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifier le statut d'un paiement MTN Money
     */
    public function checkMtnMoneyStatus($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);
            
            if ($payment->payment_method !== 'mtn_money') {
                throw new \Exception('Ce paiement n\'est pas un paiement MTN Money');
            }

            $mtnConfig = [
                'base_url' => env('MTN_MONEY_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
                'subscription_key' => env('MTN_MONEY_SUBSCRIPTION_KEY'),
                'api_user' => env('MTN_MONEY_API_USER'),
                'api_key' => env('MTN_MONEY_API_KEY'),
                'target_environment' => env('MTN_MONEY_TARGET_ENV', 'sandbox')
            ];

            // Obtenir un nouveau token
            $tokenResponse = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $mtnConfig['subscription_key'],
                'Authorization' => 'Basic ' . base64_encode($mtnConfig['api_user'] . ':' . $mtnConfig['api_key'])
            ])->post($mtnConfig['base_url'] . '/collection/token/', []);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Erreur authentification MTN Money');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Vérifier le statut du paiement
            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Target-Environment' => $mtnConfig['target_environment'],
                'Ocp-Apim-Subscription-Key' => $mtnConfig['subscription_key']
            ])->get($mtnConfig['base_url'] . '/collection/v1_0/requesttopay/' . $payment->transaction_id);

            if ($statusResponse->successful()) {
                $statusData = $statusResponse->json();
                
                if ($statusData['status'] === 'SUCCESSFUL') {
                    $payment->update([
                        'status' => 'validated',
                        'validated_at' => now(),
                        'notes' => 'Paiement MTN Money confirmé automatiquement'
                    ]);
                } elseif ($statusData['status'] === 'FAILED') {
                    $payment->update([
                        'status' => 'failed',
                        'notes' => 'Paiement MTN Money échoué: ' . ($statusData['reason'] ?? 'Erreur inconnue')
                    ]);
                }
            }

            return $payment->fresh();

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut MTN Money', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    public function getAvailablePaymentMethods()
    {
        return [
            'orange_money' => [
                'name' => 'Orange Money',
                'description' => 'Paiement via Orange Money',
                'icon' => 'orange-money-icon.png',
                'enabled' => !empty(env('ORANGE_MONEY_MERCHANT_KEY')),
                'fees' => 0, // Pas de frais supplémentaires
                'min_amount' => 100,
                'max_amount' => 1000000
            ],
            'mtn_money' => [
                'name' => 'MTN Mobile Money',
                'description' => 'Paiement via MTN Mobile Money',
                'icon' => 'mtn-money-icon.png',
                'enabled' => !empty(env('MTN_MONEY_SUBSCRIPTION_KEY')),
                'fees' => 0,
                'min_amount' => 100,
                'max_amount' => 1000000
            ],
            'bank_transfer' => [
                'name' => 'Virement Bancaire',
                'description' => 'Paiement par virement bancaire',
                'icon' => 'bank-icon.png',
                'enabled' => true,
                'fees' => 0,
                'min_amount' => 1000,
                'max_amount' => 10000000,
                'bank_accounts' => [
                    [
                        'bank_name' => 'Afriland First Bank',
                        'account_number' => '10033 00001 12345678901 23',
                        'account_name' => 'SGEE CAMEROUN',
                        'swift_code' => 'CCBACMCX'
                    ],
                    [
                        'bank_name' => 'Ecobank Cameroun',
                        'account_number' => '15002 00001 98765432109 87',
                        'account_name' => 'SGEE CAMEROUN',
                        'swift_code' => 'ECOCCMCX'
                    ]
                ]
            ],
            'cash' => [
                'name' => 'Espèces',
                'description' => 'Paiement en espèces au bureau',
                'icon' => 'cash-icon.png',
                'enabled' => true,
                'fees' => 0,
                'min_amount' => 100,
                'max_amount' => 500000,
                'office_locations' => [
                    'Yaoundé - Nlongkak, face ENSP',
                    'Douala - Akwa, Boulevard de la Liberté'
                ]
            ]
        ];
    }
}