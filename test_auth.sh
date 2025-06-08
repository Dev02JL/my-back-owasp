#!/bin/bash

echo "1. Test de la route publique :"
curl -k -X GET https://localhost:8002/api/public \
  -H "Accept: application/json"

echo -e "\n\n2. Test de la route protégée (sans authentification) :"
curl -k -X GET https://localhost:8002/api/protected \
  -H "Accept: application/json"

echo -e "\n\n3. Authentification :"
curl -k -X POST https://localhost:8002/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: application/json" \
  --data-urlencode "_username=gus@ta.ve" \
  --data-urlencode "_password=MotPasse123" \
  -c cookies.txt

echo -e "\n\n4. Test de la route protégée (avec authentification) :"
curl -k -X GET https://localhost:8002/api/protected \
  -H "Accept: application/json" \
  -b cookies.txt

echo -e "\n\n5. Test de la route admin (avec authentification) :"
curl -k -X GET https://localhost:8002/api/admin \
  -H "Accept: application/json" \
  -b cookies.txt

# Nettoyage
rm cookies.txt 