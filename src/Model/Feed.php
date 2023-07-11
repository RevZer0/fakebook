<?php

namespace Fakebook\Model;

class Feed {
    public $id;
    public User $user;
    public $post;
    public $image;
    public $date_created;


    public function __construct(User $user, string $post, string $image, ?int $id = null, $date_created = null) {
        $this->user = $user;
        $this->post = $post;
        $this->image = $image;
        $this->date_created = $date_created;
    }
}