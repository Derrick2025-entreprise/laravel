<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration with valid data
     */
    public function test_user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jean Dupont',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@test.com',
            'telephone' => '+237690123456',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['user', 'token'],
                     'message'
                 ]);

        // L'utilisateur doit exister dans la table users
        $this->assertDatabaseHas('users', [
            'email' => 'jean.dupont@test.com',
        ]);

        // Les informations détaillées sont stockées dans la table candidates
        $this->assertDatabaseHas('candidates', [
            'prenom' => 'Jean',
            'nom' => 'Dupont',
            'telephone' => '+237690123456',
        ]);
    }

    /**
     * Test registration fails with missing required field
     */
    public function test_registration_fails_with_missing_field()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'email' => 'jean@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            // prenom is missing
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['prenom']);

        $this->assertDatabaseMissing('users', [
            'email' => 'jean@test.com',
        ]);
    }

    /**
     * Test registration fails with duplicate email
     */
    public function test_registration_fails_with_duplicate_email()
    {
        User::factory()->create([
            'email' => 'existing@test.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'existing@test.com',
            'telephone' => '+237690123456',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user can login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'jean@test.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jean@test.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => ['id', 'name', 'email', 'telephone', 'status'],
                         'roles',
                         'token',
                         'token_type',
                     ],
                     'message'
                 ])
                 ->assertJson([
                     'data' => [
                         'user' => [
                             'email' => 'jean@test.com',
                             'id' => $user->id,
                         ]
                     ]
                 ]);
    }

    /**
     * Test login fails with invalid credentials (401)
     */
    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'jean@test.com',
            'password' => bcrypt('CorrectPassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jean@test.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Email ou mot de passe incorrect',
                 ]);
    }

    /**
     * Test login fails with non-existent user (401)
     */
    public function test_login_fails_with_nonexistent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'AnyPassword123!',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    /**
     * Test authenticated user can access profile
     */
    public function test_authenticated_user_can_access_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/auth/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['id', 'email', 'nom', 'prenom', 'role'],
                 ])
                 ->assertJson([
                     'data' => [
                         'id' => $user->id,
                         'email' => $user->email,
                     ]
                 ]);
    }

    /**
     * Test unauthenticated user cannot access profile (401)
     */
    public function test_unauthenticated_user_cannot_access_profile()
    {
        $response = $this->getJson('/api/auth/profile');

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated',
                 ]);
    }

    /**
     * Test invalid token is rejected (401)
     */
    public function test_invalid_token_is_rejected()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_token_12345')
                         ->getJson('/api/auth/profile');

        $response->assertStatus(401);
    }

    /**
     * Test registration password confirmation validation
     */
    public function test_registration_password_confirmation_must_match()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@test.com',
            'telephone' => '+237690123456',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
