/** vytvoreni tabulky pro ulozeni uzivatelske poznamky */

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
  osm_name int,
  import_id bigint,
  date varchar(10),
  hidden int default 0
);

SELECT AddGeometryColumn('notes','geom',4326,'POINT',2);

ALTER TABLE ONLY notes ADD CONSTRAINT pk_notes PRIMARY KEY  (id);

ALTER TABLE notes SET SCHEMA hicheck;