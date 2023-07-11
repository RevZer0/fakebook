<?php

namespace Fakebook\Model;


class User {
    public $id;
    public $name;
    public $email;
    public $password;
    public $picture;

    public function __construct(string $name, string $email, string $password, string $picture, ?string $id = null)
    {
        if (!$name or !$email or !$password) {
            throw new \RuntimeException('Name, email and password are required.');
        }
        $this->name = $name;
        $this->email = $email;
        $this->password = md5($password);
        $this->picture = $picture;
        $this->id = $id;
    }
}