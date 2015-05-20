/** vytvoreni tabulky pro ulozeni uzivatelem vyznacene trasy */

DROP TABLE IF EXISTS hicheck.parts;

DROP TABLE IF EXISTS parts;



CREATE TABLE parts (
  id SERIAL,
  tstamp TIMESTAMP default CURRENT_TIMESTAMP,
  hi_user_id text NOT NULL,
  note text,
  type int,
  osm_name int,
  import_id bigint,
  date varchar(10),
  hidden int default 0
);

SELECT AddGeometryColumn('parts','geom',4326,'LINESTRING',2);

ALTER TABLE ONLY parts ADD CONSTRAINT pk_parts PRIMARY KEY  (id);

ALTER TABLE parts SET SCHEMA hicheck;