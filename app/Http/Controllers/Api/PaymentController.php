<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    public function getPaymentMethods()
    {
        try {
            $methods = $this->paymentService->getAvailablePaymentMethods();
            
            return response()->json([
                'success' => true,
                'data' => $methods
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération méthodes paiement', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des méthodes de paiement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Initier un paiement Orange Money
     */
    public function initiateOrangeMoneyPayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100|max:1000000',
                'phone_number' => 'required|string|regex:/^(\+237|237)?[67][0-9]{8}$/',
                'payment_type' => 'required|in:enrollment_fee,tuition_fee,other'
            ]);

            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // Normaliser le numéro de téléphone
            $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

            $result = $this->paymentService->processOrangeMoneyPayment(
                $student->id,
                $request->amount,
                $phoneNumber,
                $request->payment_type
            );

            if ($result['success']) {
                return response()->json($result, Response::HTTP_CREATED);
            } else {
                return response()->json($result, Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement Orange Money', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement Orange Money'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Initier un paiement MTN Money
     */
    public function initiateMtnMoneyPayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100|max:1000000',
                'phone_number' => 'required|string|regex:/^(\+237|237)?[67][0-9]{8}$/',
                'payment_type' => 'required|in:enrollment_fee,tuition_fee,other'
            ]);

            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // Normaliser le numéro de téléphone
            $phoneNumber = $this->normalizePhoneNumber($request->phone_number);

            $result = $this->paymentService->processMtnMoneyPayment(
                $student->id,
                $request->amount,
                $phoneNumber,
                $request->payment_type
            );

            if ($result['success']) {
                return response()->json($result, Response::HTTP_CREATED);
            } else {
                return response()->json($result, Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement MTN Money', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement MTN Money'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Initier un paiement bancaire
     */
    public function initiateBankPayment(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000|max:10000000',
                'bank_name' => 'required|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'transaction_reference' => 'nullable|string|max:100',
                'payment_type' => 'required|in:enrollment_fee,tuition_fee,other'
            ]);

            $user = Auth::guard('sanctum')->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $bankDetails = [
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'transaction_reference' => $request->transaction_reference
            ];

            $result = $this->paymentService->processBankPayment(
                $student->id,
                $request->amount,
                $bankDetails,
                $request->payment_type
            );

            if ($result['success']) {
                return response()->json($result, Response::HTTP_CREATED);
            } else {
                return response()->json($result, Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement bancaire', [
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement bancaire'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($paymentId)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $payment = Payment::whereHas('student', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->findOrFail($paymentId);

            $updatedPayment = null;

            if ($payment->payment_method === 'orange_money') {
                $updatedPayment = $this->paymentService->checkOrangeMoneyStatus($paymentId);
            } elseif ($payment->payment_method === 'mtn_money') {
                $updatedPayment = $this->paymentService->checkMtnMoneyStatus($paymentId);
            }

            $paymentData = $updatedPayment ?? $payment;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $paymentData->id,
                    'reference_number' => $paymentData->reference_number,
                    'status' => $paymentData->status,
                    'amount' => $paymentData->amount,
                    'payment_method' => $paymentData->payment_method,
                    'transaction_id' => $paymentData->transaction_id,
                    'payment_date' => $paymentData->payment_date->format('d/m/Y H:i'),
                    'validated_at' => $paymentData->validated_at ? $paymentData->validated_at->format('d/m/Y H:i') : null,
                    'notes' => $paymentData->notes
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut paiement', [
                'payment_id' => $paymentId,
                'user_id' => Auth::guard('sanctum')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Callback Orange Money
     */
    public function orangeMoneyCallback(Request $request)
    {
        try {
            Log::info('Callback Orange Money reçu', $request->all());

            $orderId = $request->input('order_id');
            $status = $request->input('status');
            $payToken = $request->input('pay_token');

            if (!$orderId) {
                return response()->json(['error' => 'Order ID manquant'], 400);
            }

            $payment = Payment::where('reference_number', $orderId)->first();
            
            if (!$payment) {
                Log::warning('Paiement Orange Money non trouvé', ['order_id' => $orderId]);
                return response()->json(['error' => 'Paiement non trouvé'], 404);
            }

            if ($status === 'SUCCESS') {
                $payment->update([
                    'status' => 'validated',
                    'validated_at' => now(),
                    'notes' => 'Paiement Orange Money confirmé via callback'
                ]);
                
                Log::info('Paiement Orange Money validé', ['payment_id' => $payment->id]);
            } elseif ($status === 'FAILED') {
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Paiement Orange Money échoué via callback'
                ]);
                
                Log::info('Paiement Orange Money échoué', ['payment_id' => $payment->id]);
            }

            return response()->json(['status' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Erreur callback Orange Money', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Callback MTN Money
     */
    public function mtnMoneyCallback(Request $request)
    {
        try {
            Log::info('Callback MTN Money reçu', $request->all());

            $transactionId = $request->input('financialTransactionId');
            $status = $request->input('status');
            $externalId = $request->input('externalId');

            if (!$externalId) {
                return response()->json(['error' => 'External ID manquant'], 400);
            }

            $payment = Payment::where('reference_number', $externalId)->first();
            
            if (!$payment) {
                Log::warning('Paiement MTN Money non trouvé', ['external_id' => $externalId]);
                return response()->json(['error' => 'Paiement non trouvé'], 404);
            }

            if ($status === 'SUCCESSFUL') {
                $payment->update([
                    'status' => 'validated',
                    'validated_at' => now(),
                    'transaction_id' => $transactionId,
                    'notes' => 'Paiement MTN Money confirmé via callback'
                ]);
                
                Log::info('Paiement MTN Money validé', ['payment_id' => $payment->id]);
            } elseif ($status === 'FAILED') {
                $payment->update([
                    'status' => 'failed',
                    'notes' => 'Paiement MTN Money échoué via callback'
                ]);
                
                Log::info('Paiement MTN Money échoué', ['payment_id' => $payment->id]);
            }

            return response()->json(['status' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Erreur callback MTN Money', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }

    /**
     * Normaliser le numéro de téléphone camerounais
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        // Supprimer tous les espaces et caractères spéciaux
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Si le numéro commence par +237, le garder tel quel
        if (strpos($phone, '+237') === 0) {
            return $phone;
        }
        
        // Si le numéro commence par 237, ajouter le +
        if (strpos($phone, '237') === 0) {
            return '+' . $phone;
        }
        
        // Si le numéro commence par 6 ou 7 (format local), ajouter +237
        if (preg_match('/^[67][0-9]{8}$/', $phone)) {
            return '+237' . $phone;
        }
        
        return $phone;
    }
}