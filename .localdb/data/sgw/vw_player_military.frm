TYPE=VIEW
query=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`un`.`attack` AS `attack`,`un`.`superAttack` AS `superAttack`,`un`.`defense` AS `defense`,`un`.`superDefense` AS `superDefense`,`un`.`covert` AS `covert`,`un`.`superCovert` AS `superCovert`,`un`.`anticovert` AS `anticovert`,`un`.`superAnticovert` AS `superAnticovert`,`p`.`mil_atk` AS `mil_atk`,`p`.`mil_def` AS `mil_def`,`p`.`mil_cov` AS `mil_cov`,`p`.`mil_anti` AS `mil_anti`,`p`.`mil_total` AS `mil_total` from ((`sgw`.`users` `u` left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`)) left join `sgw`.`power` `p` on(`p`.`uid` = `u`.`uid`))
md5=0384c2a9873f6164a89fd079d15cc563
updatable=0
algorithm=0
definer_user=sgw
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001785855460194158
create-version=2
source=SELECT\n    u.uid,\n    u.uname,\n    un.attack,\n    un.superAttack,\n    un.defense,\n    un.superDefense,\n    un.covert,\n    un.superCovert,\n    un.anticovert,\n    un.superAnticovert,\n    p.mil_atk,\n    p.mil_def,\n    p.mil_cov,\n    p.mil_anti,\n    p.mil_total\nFROM users u\nLEFT JOIN units un ON un.uid = u.uid\nLEFT JOIN power p ON p.uid = u.uid
client_cs_name=utf8mb3
connection_cl_name=utf8mb3_general_ci
view_body_utf8=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`un`.`attack` AS `attack`,`un`.`superAttack` AS `superAttack`,`un`.`defense` AS `defense`,`un`.`superDefense` AS `superDefense`,`un`.`covert` AS `covert`,`un`.`superCovert` AS `superCovert`,`un`.`anticovert` AS `anticovert`,`un`.`superAnticovert` AS `superAnticovert`,`p`.`mil_atk` AS `mil_atk`,`p`.`mil_def` AS `mil_def`,`p`.`mil_cov` AS `mil_cov`,`p`.`mil_anti` AS `mil_anti`,`p`.`mil_total` AS `mil_total` from ((`sgw`.`users` `u` left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`)) left join `sgw`.`power` `p` on(`p`.`uid` = `u`.`uid`))
mariadb-version=101114
