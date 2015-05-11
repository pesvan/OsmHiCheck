
DROP TABLE IF EXISTS hicheck.notes;

DROP TABLE IF EXISTS notes;



CREATE TABLE notes (
  id SERIAL,
  tstamp TIMESTAMP default CURRENT_TIMESTAMP,
  hi_user_id text NOT NULL,
  password text,
  note text,
  image text,
  type int,
  date varchar(10)
);

SELECT AddGeometryColumn('notes','geom',4326,'POINT',2);

ALTER TABLE ONLY notes ADD CONSTRAINT pk_notes PRIMARY KEY  (id);

ALTER TABLE notes SET SCHEMA hicheck;