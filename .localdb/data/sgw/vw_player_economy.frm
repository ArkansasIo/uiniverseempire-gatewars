TYPE=VIEW
query=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`b`.`onHand` AS `onHand`,`b`.`inbank` AS `inbank`,`t`.`income` AS `incomeTech`,`t`.`unitProd` AS `unitProdTech`,`un`.`untrained` AS `untrained`,`un`.`miners` AS `miners`,`un`.`lifers` AS `lifers` from (((`sgw`.`users` `u` left join `sgw`.`bank` `b` on(`b`.`uid` = `u`.`uid`)) left join `sgw`.`technology` `t` on(`t`.`uid` = `u`.`uid`)) left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`))
md5=34eeb93084fa37ecad18f1c57a0ac826
updatable=0
algorithm=0
definer_user=sgw
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001785855460191819
create-version=2
source=SELECT\n    u.uid,\n    u.uname,\n    b.onHand,\n    b.inbank,\n    t.income AS incomeTech,\n    t.unitProd AS unitProdTech,\n    un.untrained,\n    un.miners,\n    un.lifers\nFROM users u\nLEFT JOIN bank b ON b.uid = u.uid\nLEFT JOIN technology t ON t.uid = u.uid\nLEFT JOIN units un ON un.uid = u.uid
client_cs_name=utf8mb3
connection_cl_name=utf8mb3_general_ci
view_body_utf8=select `u`.`uid` AS `uid`,`u`.`uname` AS `uname`,`b`.`onHand` AS `onHand`,`b`.`inbank` AS `inbank`,`t`.`income` AS `incomeTech`,`t`.`unitProd` AS `unitProdTech`,`un`.`untrained` AS `untrained`,`un`.`miners` AS `miners`,`un`.`lifers` AS `lifers` from (((`sgw`.`users` `u` left join `sgw`.`bank` `b` on(`b`.`uid` = `u`.`uid`)) left join `sgw`.`technology` `t` on(`t`.`uid` = `u`.`uid`)) left join `sgw`.`units` `un` on(`un`.`uid` = `u`.`uid`))
mariadb-version=101114
