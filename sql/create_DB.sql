create table User(username varchar(20),
                  email varchar(50),
                  password varchar(255), -- store a hash!
                  gender char,
                  bdate date,
                  points int,
                  isadmin tinyint,
                  primary key(email, username));

create table Song(song_ID varchar(11),
                  title varchar(50),
                  primary key(song_ID));

create table artist(name varchar(50),
                    song_ID varchar(11),
                    primary key(name, song_ID),
                    foreign key(song_ID) references Song(song_ID));
      
create table genre(name varchar(50),
                   song_ID varchar(11),
                   primary key(name, song_ID),
                   foreign key(song_ID) references Song(song_ID));

create table mood_vector(mood_name varchar(25),
                         votes int,
                         song_ID varchar(11),
                         primary key(mood_name, song_ID),
                         foreign key(song_ID) references Song(song_ID));
                         
create table uploads(username varchar(20),
                     email varchar(50),
                     song_ID varchar(11),
                     time_stamp int, -- store (UNIX) time in seconds
                     primary key(username, email, song_ID, time_stamp),
                     foreign key(email, username) references User(email, username),
                     foreign key(song_ID) references Song(song_ID));
                     
create table rates(username varchar(20),
                   email varchar(50),
                   song_ID varchar(11),
                   time_stamp int, -- store (UNIX) time in seconds
                   mood_name varchar(25),
                   primary key(username, email, song_ID, time_stamp, mood_name),
                   foreign key(mood_name) references mood_vector(mood_name),
                   foreign key(email, username) references User(email, username),
                   foreign key(song_ID) references Song(song_ID));

/* The next table is not listed in our DB design. It keeps track of - and
   limits - login attempts of users, to prevent brute-force attacks. */
create table login_attempts(user_ip int unsigned,
                            attempt int,
                            primary key(user_ip));