#!/bin/sh
# Runs once, when the MySQL data volume is first created.
# The application user already owns ${MYSQL_DATABASE}. The test suites also create and
# drop temporary databases named smartcrm_bt2_users_* / smartcrm_bt2_tc_*, so grant the
# application user rights on every smartcrm_bt2_* database (and nothing else).
set -e
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
GRANT ALL PRIVILEGES ON \`smartcrm\_bt2\_%\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
