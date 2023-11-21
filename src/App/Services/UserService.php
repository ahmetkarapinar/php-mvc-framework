<?php

declare(strict_types=1);

namespace App\Services;

use Framework\Database;
use Framework\Exceptions\ValidationException;

class UserService
{
    public function __construct(private $db)
    {
    }
    public function isEmailTaken(string $email)
    {
        $emailCount = $this->db->query(
            "SELECT COUNT(*) FROM users WHERE email = :email",
            ["email" => $email]
        )->count();

        if ($emailCount > 0) {
            throw new ValidationException(['email' => 'Email taken']);
        }
    }
    public function create(array $post)
    {
        $password = password_hash($post['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->query(
            "INSERT INTO users (email, password, age, country, social_media_url)
            VALUES (:email, :password, :age, :country, :url)",
            [
                'email' => $post['email'],
                'password' => $password,
                'age' => $post['age'],
                'country' => $post['country'],
                'url' => $post['socialMediaURL'],
            ]
        );
        session_regenerate_id();

        $_SESSION['user'] = $this->db->id();
    }
    public function login(array $formData)
    {
        $password = password_hash($formData['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $user = $this->db->query(
            "SELECT * FROM users WHERE email = :email",
            [
                'email' => $formData['email']
            ]
        )->find();

        $passwordsMatch = password_verify($formData['password'], $user['password'] ?? '');
        if (!$user || !$passwordsMatch) {
            throw new ValidationException(['password' => 'Invalid credentials']);
        }
        session_regenerate_id();
        $_SESSION['user'] = $user['id'];
    }
    public function logout()
    {
        unset($_SESSION['user']);
        session_regenerate_id();
    }
}
