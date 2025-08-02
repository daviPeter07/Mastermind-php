# Espera a DATABASE_URL existir
while [ -z "${DATABASE_URL}" ]; do
  echo "Aguardando DATABASE_URL..."
  sleep 1
done

echo "Variáveis de ambiente prontas. Iniciando o Supervisor."
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf