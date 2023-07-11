create table users (
    id int primary key auto_increment,
    email varchar(255),
    name varchar(255),
    password varchar(255),
    picture varchar(255)
) engine=InnoDB;


create table feed (
    id int primary key auto_increment,\
    user_id int not null,
    post text,
    image varchar(255),
    created_at datetime default current_timestamp
) engine=InnoDB;

