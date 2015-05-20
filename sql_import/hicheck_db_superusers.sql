/** vytvoreni tabulky pro ulozen uzvatelu s pravem skryvani uzivatelskych vstupu */

DROP TABLE IF EXISTS hicheck.superuser;

DROP TABLE IF EXISTS superuser;



CREATE TABLE superuser (
  id SERIAL,
  tstamp TIMESTAMP default CURRENT_TIMESTAMP,
  name varchar(30),
  password varchar(600)
);

ALTER TABLE ONLY superuser ADD CONSTRAINT pk_superuser PRIMARY KEY (id);

ALTER TABLE superuser SET SCHEMA hicheck;