<?php

namespace App\Services;

use App\Models\User;
use App\Utils\Logger;

class DataInitializer
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function run()
    {
        $this->checkAndFixUser('admin', 'admin123');
        $this->checkAndFixUser('teacher', 'teacher123');
    }

    private function checkAndFixUser(string $username, string $password)
    {
        $user = $this->userModel->findByUsername($username);
        
        if ($user) {
            if (!password_verify($password, $user['password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->userModel->update($user['id'], ['password' => $newHash]);
                Logger::info("Password fixed for user: {$username}");
                echo "Fixed password for user: {$username}\n";
                if ($username === 'admin') {
                    echo "Admin Hash: " . $newHash . "\n";
                }
            } else {
                echo "Password for {$username} is already correct.\n";
            }
        } else {
            echo "User {$username} not found.\n";
        }
    }
}
