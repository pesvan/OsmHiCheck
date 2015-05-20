/** vytvoreni tabulky pro ulozeni statistik - zatim vyuzito castecne */

DROP TABLE IF EXISTS hicheck.stats;

DROP TABLE IF EXISTS stats;



CREATE TABLE stats (
  id SERIAL,
  tstamp TIMESTAMP default CURRENT_TIMESTAMP,
  date varchar(10),
  relations_total real,
  relations_missing real,
  relations_wrong real, 
  missing_network real,
  missing_complete real,
  missing_osmc real,
  missing_dest real,
  wrong_network real,
  wrong_complete real,
  wrong_osmc real,
  wrong_route real,
  wrong_kct real,
  error_network real,
  error_type real,
  error_color real
);

ALTER TABLE ONLY stats ADD CONSTRAINT pk_stats PRIMARY KEY  (id);

ALTER TABLE stats SET SCHEMA hicheck;