<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserRequestParser
{
    public function getUserRequestContent(Request $request): array
    {
        if (!$request->getContent()) {
            throw new JsonException('Invalid request', Response::HTTP_BAD_REQUEST);
        }
        $data = json_decode($request->getContent(), true);

        return [
            "email" => $data["email"] ?? null,
            "password" => $data["password"] ?? null,
            "postalCode" => $data["postalCode"] ?? null,
        ];
    }

}
