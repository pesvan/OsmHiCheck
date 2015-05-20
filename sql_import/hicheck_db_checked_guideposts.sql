/** vytvoreni tabulky pro ulozeni informace k rozcestniku */
DROP TABLE IF EXISTS hicheck.checked_guideposts;

DROP TABLE IF EXISTS checked_guideposts;



CREATE TABLE checked_guideposts (
  id SERIAL,
  tstamp TIMESTAMP default CURRENT_TIMESTAMP,
  hi_user_id text NOT NULL,
  type int,
  note text,
  node varchar(50),
  image text,
  osm_name int,
  import_id bigint,
  date varchar(10),
  hidden int default 0
);

ALTER TABLE ONLY checked_guideposts ADD CONSTRAINT pk_checked_guideposts PRIMARY KEY  (id);

ALTER TABLE checked_guideposts SET SCHEMA hicheck;