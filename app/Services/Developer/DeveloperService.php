<?php

namespace App\Services\Developer;

use App\Models\Developer;
use App\Repositories\Developer\IDeveloperRepository;
use App\Services\Service;
use Illuminate\Support\Facades\Hash;

class DeveloperService extends Service implements IDeveloperService
{
    public function __construct(
        protected IDeveloperRepository $developerRepository,
    ) {
        parent::__construct($developerRepository);
    }

    public function login(string $email, string $password): ?Developer
    {
        $developer = $this->developerRepository->getBy(['email' => $email])->first();

        if (! $developer instanceof Developer) {
            return null;
        }

        if (! Hash::check($password, $developer->password)) {
            return null;
        }

        return $developer;
    }
}
