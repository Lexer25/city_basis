SET TERM ^ ;

CREATE PROCEDURE EVENT_ANALIT_MULTI (
    ID_DB INTEGER,
    ID_DEV INTEGER,
    ID_EVENTTYPE INTEGER,
    IDENTIFICATOR VARCHAR(32))
RETURNS (
    ANALIT_CODE INTEGER)
AS
DECLARE VARIABLE SINGLE_LIST INTEGER;
DECLARE VARIABLE CARD VARCHAR(32);
DECLARE VARIABLE CARD_FOR_DELETE INTEGER;
DECLARE VARIABLE CARD_FOR_LOAD INTEGER;
DECLARE VARIABLE CARD_IS_ACTIVE INTEGER;
DECLARE VARIABLE PEOPLE_IS_ACTIVE INTEGER;
DECLARE VARIABLE PASS_IS_VALIDE INTEGER;
DECLARE VARIABLE ID_PEP INTEGER;
DECLARE VARIABLE TIMESTART TIMESTAMP; /* Дата начала срока действия карта */
DECLARE VARIABLE TIMEEND TIMESTAMP; /* Дата срока окончания действия карты */
DECLARE VARIABLE CARD_IS_EXPIRED INTEGER; /* Срок дейсвтия карты истек */
begin

   people_is_active=0;
   card_is_active=0;
   pass_is_valide=0;
   analit_code=-1;
   card_for_delete=0;
   card_for_load=0;
   card_is_expired=0;
  select c.id_card, c."ACTIVE", c.id_pep, c.timestart, c.timeend
  from card c where c.id_card=:identificator and c.id_db=1 into :card,  :card_is_active, :id_pep, :timestart, :timeend;
  if(:timeend<'now') then card_is_expired = 1;

   select p."ACTIVE" from people p where p.id_pep=:id_pep into :people_is_active;

   if (exists (
                select * from ss_accessuser ssa
                    join access ac on ssa.id_accessname=ac.id_accessname and ac.id_db=ssa.id_db
                    where ssa.id_pep=:id_pep and ac.id_dev=:id_dev and ssa.id_db=1
                )) then
                /*Пользователь может ходить через эту точку прохода*/
                          pass_is_valide=1;
                         else
                /*Пользователь НЕ имеет право прохода через эту точку прохода.';  */
                        pass_is_valide=0;
     /*Анализ возможных комбинаций*/
    if((:id_eventtype = 50) or (:id_eventtype = 70) ) then  /*Анализ для события 50 - действительная карта и 70 дейтвительная карта онлайн*/
        begin
           /*Проверяю наличие карты в очереди на удаление*/
           if(exists(select * from cardindev cd where cd.id_dev=:id_dev and cd.id_card=:card and cd.operation=2 and cd.id_db=1)) then card_for_delete=1;
           /*Проверяю наличие метки Единый список у обрабатывающего контроллера*/
           select BITAND(d2.flag, 1) from device d
               join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
               where d.id_dev=:id_dev into :single_list;
               /*Если есть метка единого списка, то надо проверить возможность прохода через канал 1.*/
           /*Выполняю проверку всех возможных комбинаций*/
           if((:people_is_active = 0) and (:card_is_active = 0) and (:pass_is_valide = 0) ) then analit_code=500;  /*Ошибка! Карта не должна ходить! Пользователь и карты не активны, проход запрещен.*/
           if((:people_is_active = 0) and (:card_is_active = 0) and (:pass_is_valide = 1) ) then analit_code=501;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 0) and (:card_is_active = 1) and (:pass_is_valide = 0) ) then analit_code=502;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 0) and (:card_is_active = 1) and (:pass_is_valide = 1) ) then analit_code=503;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 1) and (:card_is_active = 0) and (:pass_is_valide = 0) ) then analit_code=504;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 1) and (:card_is_active = 0) and (:pass_is_valide = 1) ) then analit_code=505;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 0) ) then analit_code=506;  /*Ошибка! Карта не должна ходить!*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 1) and (:card_for_delete=0)) then analit_code=507; /*Ошибки нет, проход разрешен*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 1) and (:card_for_delete=1) and (:single_list=0)) then analit_code=508; /*Ошибка СКУД. Такой комбинации быть не должно*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 1) and (:card_for_delete=1) and (:single_list=1)) then analit_code=509; /*Ошибки нет, проход разрешена. Стоит метка Единый список*/
         /*14.03.2020 Добавлена обработка ситуаций, когда карат стоит в очереди на удаление*/
            if(:analit_code = 500 and :card_for_delete = 1) then analit_code=5001; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 501 and :card_for_delete = 1) then analit_code=5011; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 502 and :card_for_delete = 1) then analit_code=5021; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 503 and :card_for_delete = 1) then analit_code=5031; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 504 and :card_for_delete = 1) then analit_code=5041; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 505 and :card_for_delete = 1) then analit_code=5051; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/
            if(:analit_code = 506 and :card_for_delete = 1) then analit_code=5061; /*14.03.2020 Переходной процесс. Карта стоит в очереди на удаление*/

             if(:analit_code = 507 and :card_is_expired = 1) then analit_code=5071;
             if(:analit_code = 509 and :card_is_expired = 1) then analit_code=5091;
         end

     if((:id_eventtype = 65) or (:id_eventtype = 71))  then  /*Анализ для осбытия 65 - недействительная карта и 71 - недействительная онлайн*/
        begin

           if(exists(select * from cardindev cd where cd.id_dev=:id_dev and cd.id_card=:card and cd.operation=1 and cd.id_db=1)) then card_for_load=1;
           if((:people_is_active = 0) and (:card_is_active = 0) and (:pass_is_valide = 0) ) then analit_code=650;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 0) and (:card_is_active = 0) and (:pass_is_valide = 1) ) then analit_code=651;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 0) and (:card_is_active = 1) and (:pass_is_valide = 0) ) then analit_code=652;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 0) and (:card_is_active = 1) and (:pass_is_valide = 1) ) then analit_code=653;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 1) and (:card_is_active = 0) and (:pass_is_valide = 0) ) then analit_code=654;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 1) and (:card_is_active = 0) and (:pass_is_valide = 1) ) then analit_code=655;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 0) ) then analit_code=656;   /*Отказ в проходе правильный.*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 1) and (:card_for_delete=0)) then analit_code=657;  /*Ошибка! Карта должна ходить.*/
           if((:people_is_active = 1) and (:card_is_active = 1) and (:pass_is_valide = 1) and (:card_for_delete=1)) then analit_code=658;  /*Переходное состояние! Надо дождаться загузки карты в контроллер.*/


        end
        if(:id_eventtype = 145) then analit_code=145;   /*Проход в режиме ТЕСТ.*/
        if(:id_eventtype = 80) then analit_code=80;   /*Проход Неизвестного идентификатора.*/
        if(:id_eventtype = 48) then
            begin
                if (EXISTS (
                    select pi.id_pep from parking_gate pg
                        join parking p on p.id=pg.id_parking
                        join parking_inside pi on pi.id_parking=p.id
                        where pg.id_dev=:id_dev
                        and p.enabled>0
                        and pi.id_pep=:id_pep)) then analit_code=480; /*Отказ в повторном прохода правильный.*/
                        else analit_code=481; /*Отказ в повторном прохода НЕ правильный.*/

            end
            --добавлена аналитика запрета прохода в шлюзе. Если проход запрещен из-за правил в шлюзе, то будет код аналитики 711.
       if(:id_eventtype = 71 and :analit_code=657) then
          begin
              if(exists(select pi.id_pep from perimeter_inside    pi
                join perimeter per on per.id=pi.id_perimeter
                join perimeter_gate pg on pg.id_perimeter=pi.id_perimeter
                where pi.id_pep=:id_pep
                and pg.id_dev= :id_dev
                and ((CAST('now' AS TIMESTAmP) - pi.event_time) *24*60)< per.guest_duration)) then analit_code=711;
          end


end
^

SET TERM ; ^

GRANT SELECT ON CARD TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PEOPLE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON SS_ACCESSUSER TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON ACCESS TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON CARDINDEV TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON DEVICE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PARKING_GATE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PARKING TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PARKING_INSIDE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PERIMETER_INSIDE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PERIMETER TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT SELECT ON PERIMETER_GATE TO PROCEDURE EVENT_ANALIT_MULTI;

GRANT EXECUTE ON PROCEDURE EVENT_ANALIT_MULTI TO SYSDBA;