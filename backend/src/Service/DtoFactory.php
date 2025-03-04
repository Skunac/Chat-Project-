<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DtoFactory
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ApiResponseService $apiResponse,
    ) {
    }

    /**
     * Create a DTO from request data with validation
     *
     * @param Request $request The HTTP request
     * @param string $dtoClass The fully qualified class name of the DTO
     * @return array Response with either [dto, null] or [null, JsonResponse error]
     */
    public function createFromRequest(Request $request, string $dtoClass): array
    {
        // Parse JSON request
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return [null, $this->apiResponse->error('Invalid JSON format')];
        }

        $missingFields = $this->checkRequiredFields($dtoClass, $data);

        if (!empty($missingFields)) {
            return [null, $this->apiResponse->error(
                'Missing required fields',
                400,
                ['fields' => $missingFields]
            )];
        }

        try {
            $reflection = new \ReflectionClass($dtoClass);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                $dto = $reflection->newInstance();
            } else {
                $parameters = $constructor->getParameters();
                $constructorArgs = [];

                foreach ($parameters as $parameter) {
                    $paramName = $parameter->getName();
                    $hasDefault = $parameter->isDefaultValueAvailable();

                    if (isset($data[$paramName])) {
                        $constructorArgs[] = $data[$paramName];
                    } elseif ($hasDefault) {
                        $constructorArgs[] = $parameter->getDefaultValue();
                    } else {
                        $constructorArgs[] = null;
                    }
                }

                $dto = $reflection->newInstanceArgs($constructorArgs);
            }

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return [null, $this->apiResponse->validationError($errorMessages)];
            }

            return [$dto, null];
        } catch (\Exception $e) {
            return [null, $this->apiResponse->serverError('Error creating DTO', $e)];
        }
    }

    /**
     * Check required fields based on NotBlank constraint in constructor
     *
     * @param string $dtoClass The DTO class name
     * @param array $data The request data
     * @return array Missing field names
     */
    private function checkRequiredFields(string $dtoClass, array $data): array
    {
        $missingFields = [];
        $reflection = new \ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return $missingFields;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                continue;
            }

            if (isset($data[$paramName])) {
                continue;
            }

            $attributes = $parameter->getAttributes(NotBlank::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($attributes)) {
                $missingFields[] = $paramName;
            }
        }

        return $missingFields;
    }
}