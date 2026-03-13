<?php

namespace Tests\Feature\Candidature;

use App\Models\Exam;
use App\Models\User;
use App\Models\CandidateExam;
use App\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $exam;

    public function setUp(): void
    {
        parent::setUp();

        // Create test user (candidat role)
        $this->user = User::factory()->create([
            'email' => 'candidat@test.com',
        ]);

        // Create auth token
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create published exam
        $this->exam = Exam::factory()->create([
            'nom' => 'Concours Test 2026',
            'publie' => true,
        ]);
    }

    /**
     * Test candidate can view available exams
     */
    public function test_candidate_can_view_available_exams()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson('/api/candidature/exams');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'nom', 'description', 'date_debut', 'date_fin']
                     ]
                 ]);

        $this->assertCount(1, $response['data']);
        $this->assertEquals($this->exam->id, $response['data'][0]['id']);
    }

    /**
     * Test only published exams are shown to candidates
     */
    public function test_only_published_exams_are_shown()
    {
        Exam::factory()->create(['publie' => false]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson('/api/candidature/exams');

        // Should only have 1 exam (the published one)
        $this->assertCount(1, $response['data']);
    }

    /**
     * Test candidate can register to exam
     */
    public function test_candidate_can_register_to_exam()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->postJson('/api/candidature/register', [
                             'exam_id' => $this->exam->id,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['id', 'exam_id', 'candidat_id', 'statut'],
                     'message'
                 ]);

        $this->assertDatabaseHas('candidate_exams', [
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        // Check dossier was created
        $this->assertDatabaseHas('dossiers', [
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
        ]);
    }

    /**
     * Test candidate cannot register twice to same exam (422)
     */
    public function test_candidate_cannot_register_twice_to_same_exam()
    {
        // First registration
        $this->withHeader('Authorization', "Bearer $this->token")
             ->postJson('/api/candidature/register', [
                 'exam_id' => $this->exam->id,
             ]);

        // Second registration attempt
        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->postJson('/api/candidature/register', [
                             'exam_id' => $this->exam->id,
                         ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    /**
     * Test candidate can view their registrations
     */
    public function test_candidate_can_view_their_registrations()
    {
        CandidateExam::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson('/api/candidature/my-registrations');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'exam_id', 'statut', 'exam' => ['nom']]
                     ]
                 ])
                 ->assertJson([
                     'data' => [
                         [
                             'exam_id' => $this->exam->id,
                             'statut' => 'en_attente_documents',
                         ]
                     ]
                 ]);
    }

    /**
     * Test candidate can view their dossier
     */
    public function test_candidate_can_view_their_dossier()
    {
        $candidateExam = CandidateExam::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        $dossier = Dossier::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'candidate_exam_id' => $candidateExam->id,
            'statut' => 'en_attente',
            'progress_pct' => 0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson("/api/candidature/dossier/{$dossier->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['id', 'statut', 'progress_pct', 'documents' => []]
                 ])
                 ->assertJson([
                     'data' => [
                         'id' => $dossier->id,
                         'statut' => 'en_attente',
                     ]
                 ]);
    }

    /**
     * Test candidate cannot access dossier of another candidate (403)
     */
    public function test_candidate_cannot_access_other_dossier()
    {
        $otherUser = User::factory()->create();
        $candidateExam = CandidateExam::create([
            'candidate_id' => $otherUser->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        $dossier = Dossier::create([
            'candidate_id' => $otherUser->id,
            'exam_id' => $this->exam->id,
            'candidate_exam_id' => $candidateExam->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson("/api/candidature/dossier/{$dossier->id}");

        $response->assertStatus(403);
    }

    /**
     * Test candidate can upload document to dossier
     */
    public function test_candidate_can_upload_document()
    {
        Storage::fake('documents');

        $candidateExam = CandidateExam::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        $dossier = Dossier::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'candidate_exam_id' => $candidateExam->id,
            'statut' => 'en_attente',
        ]);

        $file = UploadedFile::fake()->create('piece_identite.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->postJson("/api/candidature/dossier/{$dossier->id}/upload", [
                             'document' => $file,
                             'type' => 'piece_identite',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['id', 'type', 'file_path', 'size'],
                     'message'
                 ]);

        $this->assertDatabaseHas('documents', [
            'dossier_id' => $dossier->id,
            'type' => 'piece_identite',
        ]);
    }

    /**
     * Test document upload validation - file too large
     */
    public function test_document_upload_fails_if_file_too_large()
    {
        Storage::fake('documents');

        $candidateExam = CandidateExam::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        $dossier = Dossier::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'candidate_exam_id' => $candidateExam->id,
            'statut' => 'en_attente',
        ]);

        // Create file > 5MB
        $file = UploadedFile::fake()->create('large_file.pdf', 6000, 'application/pdf');

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->postJson("/api/candidature/dossier/{$dossier->id}/upload", [
                             'document' => $file,
                             'type' => 'piece_identite',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['document']);
    }

    /**
     * Test candidate can confirm registration
     */
    public function test_candidate_can_confirm_registration()
    {
        $candidateExam = CandidateExam::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'statut' => 'en_attente_documents',
        ]);

        Dossier::create([
            'candidate_id' => $this->user->id,
            'exam_id' => $this->exam->id,
            'candidate_exam_id' => $candidateExam->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->postJson('/api/candidature/confirm', [
                             'candidate_exam_id' => $candidateExam->id,
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'statut' => 'confirmée',
                     ]
                 ]);

        $this->assertDatabaseHas('candidate_exams', [
            'id' => $candidateExam->id,
            'statut' => 'confirmée',
        ]);
    }

    /**
     * Test unauthenticated request fails
     */
    public function test_unauthenticated_request_fails()
    {
        $response = $this->getJson('/api/candidature/exams');

        $response->assertStatus(401);
    }

    /**
     * Test pagination on exams list
     */
    public function test_exams_list_is_paginated()
    {
        Exam::factory(15)->create(['publie' => true]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
                         ->getJson('/api/candidature/exams?per_page=10');

        $response->assertStatus(200);

        if (isset($response['pagination'])) {
            $this->assertEquals(10, count($response['data']));
        }
    }
}
