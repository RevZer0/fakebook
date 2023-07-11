<?php

namespace Fakebook\Repository;

use Fakebook\Model\Feed;

class FeedRepository extends AbstractRepository {

    public function create(Feed $post)
    {
        $this->connection->exec("insert into feed (user_id, post, image) values ('{$post->user->id}', '{$post->post}', '{$post->image}')");
        $post->id = $this->connection->lastInsertId();

        return $post;
    }

    public function getFeed() {
        $stm = $this->connection->prepare("select * from feed order by created_at desc");
        $stm->execute();
        $usersRepository = new UsersRepository($this->connection);
        $feed = array_map(function($row) use ($usersRepository) {
            $author = $usersRepository->getUser($row['user_id']);

            return new Feed($author, $row['post'], $row['image'], $row['id'], $row['created_at']);
        }, $stm->fetchAll());

        return $feed;
    }
}