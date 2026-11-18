SET TERM ^ ;

CREATE PROCEDURE DEVICEEVENTS_INSERT (
    ID_DB INTEGER,
    ID_EVENTTYPE INTEGER,
    ID_CTRL INTEGER,
    ID_READER INTEGER,
    NOTE VARCHAR(100),
    "TIME" TIMESTAMP,
    ID_VIDEO INTEGER,
    ID_USER INTEGER,
    ESS1 INTEGER,
    ESS2 INTEGER,
    IDSOURCE INTEGER,
    IDSERVERTS INTEGER)
RETURNS (
    ID_EVENT INTEGER)
AS
DECLARE VARIABLE ID_DEV INTEGER;
DECLARE VARIABLE ID_PLAN INTEGER;
DECLARE VARIABLE FLAGCARD INTEGER;
DECLARE VARIABLE ID_ORG INTEGER;
DECLARE VARIABLE ID_PEP INTEGER;
DECLARE VARIABLE FLAG INTEGER;
DECLARE VARIABLE SURNAME VARCHAR(50);
DECLARE VARIABLE NAME BLOB SUB_TYPE 0 SEGMENT SIZE 80;
DECLARE VARIABLE PATRONYMIC VARCHAR(50);
DECLARE VARIABLE ID_CARD VARCHAR(32);
DECLARE VARIABLE GUESTNOTE VARCHAR(250);
DECLARE VARIABLE FULLNAME VARCHAR(410);
DECLARE VARIABLE SOUND BLOB SUB_TYPE 0 SEGMENT SIZE 80;
DECLARE VARIABLE DOOR_NAME VARCHAR(50);
DECLARE VARIABLE SERVER_NAME VARCHAR(50);
DECLARE VARIABLE ANALIT INTEGER;
DECLARE VARIABLE S_BEFOR TIMESTAMP;
DECLARE VARIABLE S_END TIMESTAMP;
DECLARE VARIABLE EXEC_TIME DOUBLE PRECISION;
Begin
s_befor='now';

select et."ACTIVE" from eventtype et where et.id_eventtype=:id_eventtype into :flag;
--flag=1;

if(flag>0) then
    begin


select ID_DEV from device where  (ID_DB=:ID_DB) AND (ID_CTRL=:ID_CTRL) AND (ID_READER=:id_reader)  INTO :ID_DEV;
--INSERT INTO EVENTS2 (ID_DB,ID_DEV, ID_EVENTTYPE,ID_OBJECT,DATETIME,NOTE,DEVTIME,ID_SERVER,ID_EVENTTS) VALUES (1,:ID_DEV,:ID_EVENTTYPE,1,'NOW',:NOTE,:"TIME",:IDSOURCE,:IDSERVERTS);

IF (:id_eventtype IN (49,53,54,57,58, 69, 83, 84, 85, 86)) THEN
    BEGIN
      SELECT MAX(ID_DEV), MAX(ID_PLAN) FROM DEVICE WHERE (ID_DB=:ID_DB) AND (ID_CTRL=:ID_CTRL) AND (ID_READER=:id_reader)
      INTO :ID_DEV, :ID_PLAN;
      s_end= 'now';
        exec_time=s_end-s_befor;
      INSERT INTO EVENTS(ID_DB, ID_EVENTTYPE, DATETIME, ID_DEV, ID_PLAN, IDSOURCE, IDSERVERTS, EXEC_TIME)
      VALUES (:ID_DB, :ID_EVENTTYPE, :"TIME", :ID_DEV, :ID_PLAN, :IDSOURCE, :IDSERVERTS,:exec_time);
    END

else IF (:id_eventtype IN (46,47,48,50, 145, 81, 75, 146, 147, 148, 70, 71)) THEN
    BEGIN
      SELECT MAX(ID_DEV) FROM DEVICE WHERE (ID_DB=:ID_DB) AND (ID_CTRL=:ID_CTRL) AND (ID_READER=:id_reader)
      INTO :ID_DEV;

      fullname = ' <'||coalesce(:note, '')||'>';
      id_pep = :ess1;                            
      id_org = :ess2;

      
      
      select C.FLAG, c.ID_PEP from card c where (c.ID_CARD = :NOTE)
      into :FLAGCARD, :id_pep;

      SELECT P.ID_ORG, P.SOUND, P.ID_PLAN, P.FLAG, P.note
        , coalesce(' '||p.surname,'')||coalesce(' '||p.name,'')||coalesce(' '||p.patronymic,'')
      FROM PEOPLE P where (P.ID_PEP = :id_pep)
      INTO :id_org, :sound, :id_plan, :flag, :GUESTNOTE
        , :fullname
      ;

      if ((:id_eventtype=46) and (coalesce(:id_pep, 0)<>0)) then
         id_eventtype=65;

         if ((:id_eventtype=50) and (:id_pep is null)) then id_eventtype=80;
         if ((:id_eventtype=70) and (:id_pep is null)) then id_eventtype=80;

      if ((:id_eventtype=50) and (bitAnd(:flagcard,1)<>0) ) then  fullname = fullname||coalesce(' в '||:GUESTNOTE, '');
      if ((:id_eventtype=70) and (bitAnd(:flagcard,1)<>0) ) then  fullname = fullname||coalesce(' в '||:GUESTNOTE, '');



      if ((:id_eventtype=145) and (bitAnd(:flagcard,1)<>0) ) then
         fullname = fullname||coalesce(' в '||:GUESTNOTE, '');

       analit=0;
       --if((:id_eventtype=50) or (:id_eventtype=47) or (:id_eventtype=65)) then
        --execute procedure event_analit :id_db, :ID_DEV, :ID_PEP, :id_eventtype returning_values :analit;
        --execute procedure event_analit_multi :id_db, :ID_DEV, :ID_PEP, :id_eventtype, :note returning_values :analit;

      fullname = substring(:fullname from 2 for 100);
       s_end= 'now';
        exec_time=s_end-s_befor;

      INSERT INTO EVENTS(ID_DB, ID_EVENTTYPE, DATETIME, ID_DEV, ID_PLAN, ID_CARD, ANALIT,  ESS1, ESS2, NOTE, IDSOURCE, IDSERVERTS, EXEC_TIME)
      VALUES (:ID_DB, :ID_EVENTTYPE, :"TIME", :ID_DEV, :ID_PLAN, :NOTE, :analit ,:ID_PEP, :ID_ORG, :fullname, :IDSOURCE, :IDSERVERTS,:exec_time);
    END

else  IF (:id_eventtype IN (51,52,55,56)) THEN
    BEGIN
      SELECT MAX(ID_DEV), MAX(ID_PLAN) FROM DEVICE WHERE (ID_DB=:ID_DB) AND (ID_CTRL=:ID_CTRL) AND (ID_READER=:id_reader)
      INTO :ID_DEV, :ID_PLAN;
      s_end= 'now';
        exec_time=s_end-s_befor;
      INSERT INTO EVENTS(ID_DB, ID_EVENTTYPE, DATETIME, ID_DEV, ID_PLAN, ESS1, IDSOURCE, IDSERVERTS, EXEC_TIME)
      VALUES (:ID_DB, :ID_EVENTTYPE, :"TIME", :ID_DEV, :ID_PLAN, :ESS1, :IDSOURCE, :IDSERVERTS,:exec_time);
    END



else begin   /*Неизвестное событие. Редакция Бухаров А.В. 11.07.2021. Работает с АСервером 1.2.0.33*/
        fullname='';
        door_name='no_device_name';
        server_name='no_server_name';
        select d.name, s.name from device d
        join server s on d.id_server=s.id_server
        where d.id_ctrl=:id_ctrl  and d.id_reader is null
         into :door_name, :server_name ;
        if(:id_ctrl is null) then fullname = 'no id_ctrl';
        if(:id_reader is null) then fullname = fullname||' no id_reader';

                             
        if(:id_eventtype is not null) then fullname = fullname || 'Unknow event='||:id_eventtype;
        if(:note is not null) then fullname = fullname || ',note="' ||:note;
        if(:door_name is not null) then fullname = fullname ||'",dev="'||:door_name;
        if(:server_name is not null) then fullname = fullname ||'",serv="'||:server_name;
        if(:idsource is not null) then fullname = fullname ||'", id_ser='||:idsource;
        if(:idserverts is not null) then fullname = fullname ||',id_event='||:idserverts;




        INSERT INTO events (ID_DB, ID_EVENTTYPE, DATETIME, ID_DEV, ID_PLAN, IDSOURCE, IDSERVERTS, NOTE)
        VALUES (:ID_DB, 0, :"TIME", :ID_DEV, :ID_PLAN, :IDSOURCE, :IDSERVERTS,
        substring(:fullname  from 1 for 100));

    end

select distinct gen_id(gen_event_id,0)
    from RDB$DATABASE
    into :id_event;
    suspend;

post_event('EventMonitor');   
--if ((:id_eventtype=50) and (:sound is not null)) then post_event('EventSound');
--if ((:id_eventtype=50) and (bitAND(:flag,128)<>0)) then post_event('EventSpeak');

     end
      else
      id_event = -1;
      suspend;

end
^

SET TERM ; ^

DESCRIBE PROCEDURE DEVICEEVENTS_INSERT
'Процедура вставки события в таблицу events';

GRANT SELECT ON EVENTTYPE TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT SELECT ON DEVICE TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT INSERT ON EVENTS TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT SELECT ON CARD TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT SELECT ON PEOPLE TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT SELECT ON SERVER TO PROCEDURE DEVICEEVENTS_INSERT;

GRANT EXECUTE ON PROCEDURE DEVICEEVENTS_INSERT TO SYSDBA;