<?php 

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class UserChecker implements UserCheckerInterface {

    public function checkPreAuth(UserInterface $user)
    {
        if(!$user instanceof User){
            return;
        }
        if(!$user->isActive()){
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        if(!$user instanceof User){
            return;
        }

        if(!$user->isActive()){
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé.');
        }


    }
}