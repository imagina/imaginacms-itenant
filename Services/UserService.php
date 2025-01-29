<?php

namespace Modules\Itenant\Services;

use Illuminate\Support\Facades\Auth;

use Modules\User\Repositories\UserRepository;
use Modules\User\Repositories\UserTokenRepository;

class UserService
{
   
    private $log = "Itenant:: UserService|| ";
    private $userRepository;
    private $userTokenRepository;

    public function __construct(UserRepository $userRepository,UserTokenRepository $userTokenRepository)
    {
        $this->userRepository = $userRepository;
        $this->userTokenRepository = $userTokenRepository;
    }

    /**
     * update user in Tenant DB
     */
    public function updateUser(array $data, $userId)
    {
        \Log::info($this->log."updateUser");

        $userLogged = $data['user'];

        $dataToUpdate = [
            'first_name' => $userLogged->first_name ?? "",
            'last_name' => $userLogged->last_name ?? "",
            'email' => $userLogged->email,
            'password' =>  $userLogged->password
        ];

        \DB::table("users")->where('id',$userId)->update($dataToUpdate);

    }


    /**
     * autnenticate user
     */
    public function authenticate(array $data, $userId = null)
    {

        \Log::info($this->log."authenticate| userId: ".$userId);
        
        //Login and Token
        $user = Auth::loginUsingId($userId);
        $personalAccessTokenResult  = $user->createToken('Laravel Password Grant Client');

        $response = [
            "token" => $personalAccessTokenResult->accessToken,
            'expiresAt' => $personalAccessTokenResult->token->expires_at
        ];

        return $response;
        
    }

    
}