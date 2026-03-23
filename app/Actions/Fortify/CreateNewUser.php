<?php

namespace App\Actions\Fortify;

use App\Actions\Organization\AcceptInvitation;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly AcceptInvitation $acceptInvitation) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'invitation_token' => ['nullable', 'string'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        if (! empty($input['invitation_token'])) {
            $tokenHash = hash('sha256', $input['invitation_token']);
            $invitation = OrganizationInvitation::query()
                ->where('token_hash', $tokenHash)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($invitation) {
                $this->acceptInvitation->handle($invitation, $user);
            }
        }

        return $user;
    }
}
