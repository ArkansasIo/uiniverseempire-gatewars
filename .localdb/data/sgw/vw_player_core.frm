TYPE=VIEW
query=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`u`.`email` AS `email`,`u`.`allyid` AS `allyid`,`u`.`lastLogin` AS `lastLogin`,`ud`.`actionTurns` AS `actionTurns`,`b`.`onHand` AS `onHand`,`b`.`inbank` AS `inbank`,`un`.`attack` AS `attack`,`un`.`defense` AS `defense`,`un`.`covert` AS `covert`,`un`.`anticovert` AS `anticovert`,`un`.`untrained` AS `untrained`,`t`.`income` AS `income`,`t`.`unitProd` AS `unitProd`,`p`.`overall` AS `overall`,`p`.`mil_total` AS `mil_total` from (((((`sgw`.`users` `u` left join `sgw`.`userdata` `ud` on(`ud`.`uid` = `u`.`uid`)) left join `sgw`.`bank` `b` on(`b`.`uid` = `u`.`uid`)) left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`)) left join `sgw`.`technology` `t` on(`t`.`uid` = `u`.`uid`)) left join `sgw`.`power` `p` on(`p`.`uid` = `u`.`uid`))
md5=07e8f037fea28d6125cf34cc253c2647
updatable=0
algorithm=0
definer_user=sgw
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001785855460188902
create-version=2
source=SELECT\n    u.uid,\n    u.uname,\n    u.email,\n    u.allyid,\n    u.lastLogin,\n    ud.actionTurns,\n    b.onHand,\n    b.inbank,\n    un.attack,\n    un.defense,\n    un.covert,\n    un.anticovert,\n    un.untrained,\n    t.income,\n    t.unitProd,\n    p.overall,\n    p.mil_total\nFROM users u\nLEFT JOIN userdata ud ON ud.uid = u.uid\nLEFT JOIN bank b ON b.uid = u.uid\nLEFT JOIN units un ON un.uid = u.uid\nLEFT JOIN technology t ON t.uid = u.uid\nLEFT JOIN power p ON p.uid = u.uid
client_cs_name=utf8mb3
connection_cl_name=utf8mb3_general_ci
view_body_utf8=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`u`.`email` AS `email`,`u`.`allyid` AS `allyid`,`u`.`lastLogin` AS `lastLogin`,`ud`.`actionTurns` AS `actionTurns`,`b`.`onHand` AS `onHand`,`b`.`inbank` AS `inbank`,`un`.`attack` AS `attack`,`un`.`defense` AS `defense`,`un`.`covert` AS `covert`,`un`.`anticovert` AS `anticovert`,`un`.`untrained` AS `untrained`,`t`.`income` AS `income`,`t`.`unitProd` AS `unitProd`,`p`.`overall` AS `overall`,`p`.`mil_total` AS `mil_total` from (((((`sgw`.`users` `u` left join `sgw`.`userdata` `ud` on(`ud`.`uid` = `u`.`uid`)) left join `sgw`.`bank` `b` on(`b`.`uid` = `u`.`uid`)) left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`)) left join `sgw`.`technology` `t` on(`t`.`uid` = `u`.`uid`)) left join `sgw`.`power` `p` on(`p`.`uid` = `u`.`uid`))
mariadb-version=101114
