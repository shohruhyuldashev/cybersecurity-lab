-- Main DB
CREATE DATABASE IF NOT EXISTS webapp_main;
USE webapp_main;

CREATE TABLE IF NOT EXISTS users(
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100),
    password VARCHAR(100)
);

INSERT INTO users(username,password)
VALUES ('admin','adminpass'),
       ('guest','guestpass');

CREATE TABLE IF NOT EXISTS products(
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT
);

INSERT INTO products(name,description)
VALUES
('Widget','Small widget'),
('Gadget','Useful gadget'),
('Thingamajig','Mystery item');

CREATE TABLE IF NOT EXISTS comments(
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    text TEXT
);

INSERT INTO comments(product_id,text)
VALUES (1,'Nice product!');


-- Lab1
CREATE DATABASE IF NOT EXISTS lab1_db;
USE lab1_db;
CREATE TABLE flag(flag VARCHAR(255));
INSERT INTO flag VALUES('FLAG{basic_sqli_master}');

-- Lab2
CREATE DATABASE IF NOT EXISTS lab2_db;
USE lab2_db;
CREATE TABLE flag(flag VARCHAR(255));
INSERT INTO flag VALUES('FLAG{login_bypass_1337}');

-- Lab3
CREATE DATABASE IF NOT EXISTS lab3_db;
USE lab3_db;
CREATE TABLE flag(flag VARCHAR(255));
INSERT INTO flag VALUES('FLAG{union_extraction_pro}');

-- Lab4
CREATE DATABASE IF NOT EXISTS lab4_db;
USE lab4_db;
CREATE TABLE flag(flag VARCHAR(255));
INSERT INTO flag VALUES('FLAG{boolean_blind_ninja}');

-- Lab5
CREATE DATABASE IF NOT EXISTS lab5_db;
USE lab5_db;
CREATE TABLE flag(flag VARCHAR(255));
INSERT INTO flag VALUES('FLAG{stored_injection_chain}');