# Wave REST Server

### MySQL Database

DDLs:

```mysql
CREATE TABLE sessions
(
  session_id         INTEGER     NOT NULL PRIMARY KEY AUTO_INCREMENT,
  session_token      VARCHAR(36) NOT NULL CHECK ( LENGTH(session_token) > 35 ) UNIQUE,
  source             VARCHAR(36) NOT NULL CHECK ( LENGTH(source) > 35 ) UNIQUE,
  user               INTEGER     NOT NULL,
  creation_timestamp TIMESTAMP   NOT NULL,
  last_updated       TIMESTAMP   NOT NULL,
  active             BOOLEAN     NOT NULL,
  FOREIGN KEY (user)
    REFERENCES users (user_id)
    ON DELETE CASCADE
)
```

```mysql
CREATE TABLE users
(
  user_id  INTEGER                 NOT NULL PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(32)             NOT NULL CHECK ( LENGTH(username) > 4 ) UNIQUE,
  password VARCHAR(255)            NOT NULL,
  name     VARCHAR(64)             NOT NULL,
  surname  VARCHAR(64)             NOT NULL,
  picture  VARCHAR(255),
  phone    VARCHAR(19) CHECK ( LENGTH(phone) > 4 ),
  theme    VARCHAR(1) DEFAULT 'L'  NOT NULL,
  language VARCHAR(2) DEFAULT 'EN' NOT NULL CHECK ( LENGTH(language) > 1 ),
  active   BOOLEAN                 NOT NULL
)
```

```mysql
CREATE TABLE contacts
(
  first_user  INTEGER    NOT NULL,
  second_user INTEGER    NOT NULL,
  status      VARCHAR(1) NOT NULL,
  blocked_by  INTEGER,
  chat        VARCHAR(36) CHECK ( LENGTH(chat) > 35 ) UNIQUE,
  active      BOOLEAN    NOT NULL,
  FOREIGN KEY (first_user)
    REFERENCES users (user_id)
    ON DELETE CASCADE,
  FOREIGN KEY (second_user)
    REFERENCES users (user_id)
    ON DELETE CASCADE,
  FOREIGN KEY (blocked_by)
    REFERENCES users (user_id)
    ON DELETE SET NULL
)
```

```mysql
CREATE TABLE `groups`
(
  group_id INTEGER      NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name     VARCHAR(64)  NOT NULL,
  info     VARCHAR(225) NOT NULL,
  picture  VARCHAR(225) UNIQUE,
  chat     VARCHAR(36)  NOT NULL CHECK ( LENGTH(chat) > 35 ) UNIQUE,
  active   BOOLEAN      NOT NULL
)
```

```mysql
CREATE TABLE groups_members
(
  user    INTEGER    NOT NULL,
  `group` INTEGER    NOT NULL,
  state   VARCHAR(1) NOT NULL,
  muted   BOOLEAN    NOT NULL,
  active  BOOLEAN    NOT NULL,
  FOREIGN KEY (user)
    REFERENCES users (user_id)
    ON DELETE CASCADE,
  FOREIGN KEY (`group`)
    REFERENCES `groups` (group_id)
    ON DELETE CASCADE
)
```
