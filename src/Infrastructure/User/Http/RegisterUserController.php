<?php

namespace App\Infrastructure\User\Http;

use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\RegisterUser;
use App\Application\User\RegisterUserRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterUserController extends ApiAbstractController
{
    public function __construct(
        private RegisterUser $registerUser,
        private ValidatorInterface $validator)
    {
    }

    #[Route('/api/user/register', name: 'app_user_register')]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = RegisterUserRequest::fromArray($request->toArray());
            $violations = $this->validator->validate($user);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                return $this->badRequest('Invalid parameter', $errors);
            }

            $this->registerUser->execute($user);
        } catch (JsonException $e) {
            return $this->badRequest('Invalid json format.');
        } catch (EmailAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch(BadRequestException $e) {
            return $this->badRequest($e->getMessage());
        } catch (Exception $e) {
            return $this->error();
        }


        return $this->created();
    }
}
