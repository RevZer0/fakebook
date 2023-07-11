<?php

namespace Fakebook\Repository;

use Fakebook\Model\User;

class UsersRepository extends AbstractRepository {

    public function create(User $user) {
        $this->connection->exec("insert into users (email, password, name, picture) values ('{$user->email}', '{$user->password}', '{$user->name}', '{$user->picture}')");
        $user->id = $this->connection->lastInsertId();

        return $user;
    }

    public function checkUserExists(User $user) {
        $stm = $this->connection->prepare("select id from users where email = ? limit 1");
        $stm->bindParam(1, $user->email);
        $stm->execute();
        if ($stm->fetch()) {
            throw new \RuntimeException('User already exists.');
        }
    }

    public function authenticateUser(string $email, string $password) {
        $password = md5($password);
        $stm = $this->connection->prepare("select * from users where email = '{$email}' and password = '{$password}' limit 1");
        $stm->execute();

        if (!($userdata = $stm->fetch())) {
            throw new \RuntimeException('Invalid login or password.');
        }   
        return new User($userdata['name'], $userdata['email'], $userdata['password'], $userdata['picture'], $userdata['id']);
    }

    public function getUser(string $id) {
        $stm = $this->connection->prepare("select * from users where id = {$id}");
        $stm->execute();
        if (!($userdata = $stm->fetch())) {
            throw new \RuntimeException('User not found.');
        }
        return new User($userdata['name'], $userdata['email'], $userdata['password'], $userdata['picture'], $userdata['id']);   
    }


    public function update(User $user) {
        $this->connection->exec("update users set password = '{$user->password}', name = '{$user->name}', picture = '{$user->picture}' where id = {$user->id}");

        return $user;
    }
}