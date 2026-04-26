<?php
declare(strict_types=1);

namespace ReportaBlu\Application;

use ReportaBlu\Application\Exceptions\ValidationException;
use ReportaBlu\Domain\Contracts\UserRepositoryInterface;

final class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function authenticate(string $email, string $password): array
    {
        $errors = [];
        $email = trim($email);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Informe um email valido.';
        }

        if ($password === '') {
            $errors[] = 'Informe sua senha.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            throw new ValidationException(['Email ou senha invalidos.']);
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }

    public function register(string $name, string $email, string $password, string $confirmPassword): void
    {
        $errors = [];

        $name = trim($name);
        $email = trim($email);

        if ($name === '') {
            $errors[] = 'Informe seu nome.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Informe um email valido.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'A senha deve ter ao menos 6 caracteres.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'A confirmacao de senha nao confere.';
        }

        if ($email !== '' && $this->userRepository->emailExists($email)) {
            $errors[] = 'Este email ja esta cadastrado.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $this->userRepository->create(
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            'morador'
        );
    }
}
