-- Banco separado usado pela suíte de testes (tests/bootstrap.php roda as
-- migrations completas contra ele a cada execução). Precisa ser Postgres,
-- não SQLite: a stored procedure do simulador (CreateSimulatorFunction) é
-- PL/pgSQL puro e não roda em outro driver.
--
-- Scripts em docker-entrypoint-initdb.d só executam na primeira vez que o
-- volume do container é criado. Se o volume "pg-data" já existir de antes
-- desta mudança, crie o banco manualmente:
--   docker exec -it pgsql-sul-prev psql -U admin -d adesao-sulprev-db -c "CREATE DATABASE \"adesao-sulprev-test\";"
CREATE DATABASE "adesao-sulprev-test";
