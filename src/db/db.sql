create table users(
    userId         varchar(36)  PRIMARY KEY, 
    username       varchar(255), 
    password       varchar(255), 
    email          varchar(255)  unique, 
    role           ENUM('admin', 'user'), 
    created_at     TIMESTAMP DEFAULT   CURRENT_TIMESTAMP 
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

create table quizzes(
    Id              varchar(36) PRIMARY KEY, 
    creator_id      varchar(36), 
    title           varchar(255), 
    user_id         varchar(36),
    description     text, 
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY     (creator_id)    REFERENCES users(userId),
    FOREIGN KEY     (user_id)       REFERENCES users(userId)

);

create table questions(
    Id              Int      AUTO_INCREMENT PRIMARY KEY, 
    quiz_id         varchar(36),
    question_text   text, 
    FOREIGN KEY     (quiz_id)   REFERENCES  quizzes(Id)            
);

create table options(
    Id              Int      AUTO_INCREMENT   PRIMARY KEY, 
    question_id     Int, 
    option_text     text, 
    is_correct      Boolean,
    FOREIGN KEY     (question_id)   REFERENCES questions(Id) 
);

create table submissions(
    id              varchar(36)    PRIMARY KEY, 
    quiz_id         varchar(36), 
    user_id         varchar(36),
    submitted_at    TIMESTAMP  DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY     (user_id)       REFERENCES users(userId), 
    FOREIGN KEY     (quiz_id)       REFERENCES quizzes(Id)
);

create table answers(
    id                  Int           AUTO_INCREMENT           PRIMARY KEY, 
    question_id         Int, 
    option_id           Int,
    score               int, 
    submission_id       varchar(36), 
    FOREIGN KEY         (question_id)  REFERENCES  questions(Id), 
    FOREIGN KEY         (option_id) REFERENCES options(Id),
    FOREIGN KEY         (submission_id) REFERENCES submissions(Id)
);

create table result(
    id                  varchar(36)   PRIMARY KEY,
    total_score         int,
    user_id             varchar(36), 
    quiz_id             varchar(36),
    submission_id       varchar(36),
    FOREIGN KEY         (user_id)   REFERENCES   users(userId),
    FOREIGN KEY         (quiz_id)   REFERENCES   quizzes(Id)
);